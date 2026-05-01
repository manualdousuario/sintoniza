<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Sintoniza\Library\Url;

final class DedupAndEnforceFeedUniqueness extends AbstractMigration
{
    private const CHUNK = 2000;

    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $before = $this->snapshot();

        $this->dedupFeedsByExactUrl();
        $this->normalizeAndRedupFeeds();
        $this->dedupSubscriptionsByExactUrlUser();
        $this->normalizeAndRedupSubscriptions();
        $this->cleanupOrphanEpisodes();
        $this->ensureUniqueIndexes();

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

        $this->report($before, $this->snapshot());
    }

    public function down(): void
    {
    }

    private function dedupFeedsByExactUrl(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_feed_canon_x`');
        $this->execute('CREATE TABLE `_feed_canon_x` (
            feed_url     VARCHAR(512) NOT NULL,
            canonical_id INT          NOT NULL,
            INDEX (feed_url(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('INSERT INTO `_feed_canon_x` (feed_url, canonical_id)
            SELECT feed_url, id FROM (
                SELECT id, feed_url,
                       ROW_NUMBER() OVER (
                           PARTITION BY feed_url
                           ORDER BY (last_fetch > 0) DESC, last_fetch DESC, fetch_failures ASC, id ASC
                       ) AS rn
                FROM feeds
            ) ranked
            WHERE rn = 1');

        $this->execute('DROP TABLE IF EXISTS `_feed_map_x`');
        $this->execute('CREATE TABLE `_feed_map_x` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_feed_map_x` (old_id, new_id)
            SELECT f.id, c.canonical_id
            FROM feeds f
            JOIN `_feed_canon_x` c ON c.feed_url = f.feed_url
            WHERE f.id <> c.canonical_id');

        $this->remapFeedReferences();

        $this->execute('DELETE f FROM feeds f
            JOIN `_feed_map_x` m ON m.old_id = f.id');

        $this->execute('DROP TABLE IF EXISTS `_feed_map_x`');
        $this->execute('DROP TABLE IF EXISTS `_feed_canon_x`');
    }

    private function normalizeAndRedupFeeds(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_feed_norm_x`');
        $this->execute('CREATE TABLE `_feed_norm_x` (
            old_id INT          NOT NULL PRIMARY KEY,
            norm   VARCHAR(512) NOT NULL,
            INDEX (norm(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $maxId = 0;
        do {
            $rows = $this->fetchAll(sprintf(
                'SELECT id, feed_url FROM feeds WHERE id > %d ORDER BY id LIMIT %d',
                $maxId,
                self::CHUNK
            ));
            if (!$rows) {
                break;
            }

            $values = [];
            foreach ($rows as $row) {
                $id    = (int) $row['id'];
                $maxId = max($maxId, $id);
                $norm  = Url::normalizeFeed((string) $row['feed_url']);
                if ($norm === '') {
                    continue;
                }
                $values[] = sprintf(
                    '(%d, %s)',
                    $id,
                    $this->quote($norm)
                );
            }

            if ($values) {
                $this->execute('INSERT INTO `_feed_norm_x` (old_id, norm) VALUES ' . implode(',', $values));
            }
        } while (count($rows) === self::CHUNK);

        $this->execute('DROP TABLE IF EXISTS `_feed_map_x`');
        $this->execute('CREATE TABLE `_feed_map_x` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_feed_map_x` (old_id, new_id)
            SELECT a.old_id, ranked.canonical_id
            FROM `_feed_norm_x` a
            JOIN (
                SELECT old_id AS canonical_id, norm FROM (
                    SELECT n.old_id, n.norm,
                           ROW_NUMBER() OVER (
                               PARTITION BY n.norm
                               ORDER BY (f.last_fetch > 0) DESC, f.last_fetch DESC,
                                        f.fetch_failures ASC, f.id ASC
                           ) AS rn
                    FROM `_feed_norm_x` n
                    JOIN feeds f ON f.id = n.old_id
                ) t WHERE rn = 1
            ) ranked ON ranked.norm = a.norm
            WHERE a.old_id <> ranked.canonical_id');

        $this->remapFeedReferences();

        $this->execute('INSERT IGNORE INTO feed_aliases (url, feed_id, created_at)
            SELECT n.norm, m.new_id, UNIX_TIMESTAMP()
            FROM `_feed_map_x` m
            JOIN `_feed_norm_x` n ON n.old_id = m.old_id
            JOIN feeds f          ON f.id     = m.new_id
            WHERE n.norm <> f.feed_url');

        $this->execute('DELETE f FROM feeds f
            JOIN `_feed_map_x` m ON m.old_id = f.id');

        $this->writeNormalizedFeedUrls();

        $this->execute('DROP TABLE IF EXISTS `_feed_map_x`');
        $this->execute('DROP TABLE IF EXISTS `_feed_norm_x`');
    }

    private function remapFeedReferences(): void
    {
        $this->execute('UPDATE subscriptions s
            JOIN `_feed_map_x` m ON m.old_id = s.feed
            SET s.feed = m.new_id');

        $this->execute('UPDATE IGNORE episodes e
            JOIN `_feed_map_x` m ON m.old_id = e.feed
            SET e.feed = m.new_id');

        $this->execute('DROP TABLE IF EXISTS `_episode_remap_x`');
        $this->execute('CREATE TABLE `_episode_remap_x` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_episode_remap_x` (old_id, new_id)
            SELECT e_old.id, e_new.id
            FROM episodes e_old
            JOIN `_feed_map_x` m  ON m.old_id  = e_old.feed
            JOIN episodes      e_new ON e_new.feed = m.new_id
                                     AND LEFT(e_new.media_url, 255) = LEFT(e_old.media_url, 255)');

        $this->execute('UPDATE episodes_actions ea
            JOIN `_episode_remap_x` er ON er.old_id = ea.episode
            SET ea.episode = er.new_id');

        $this->execute('DROP TABLE IF EXISTS `_episode_remap_x`');

        $this->execute('UPDATE IGNORE feed_aliases a
            JOIN `_feed_map_x` m ON m.old_id = a.feed_id
            SET a.feed_id = m.new_id');
    }

    private function writeNormalizedFeedUrls(): void
    {
        $maxId = 0;
        do {
            $rows = $this->fetchAll(sprintf(
                'SELECT id, feed_url FROM feeds WHERE id > %d ORDER BY id LIMIT %d',
                $maxId,
                self::CHUNK
            ));
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $id    = (int) $row['id'];
                $orig  = (string) $row['feed_url'];
                $norm  = Url::normalizeFeed($orig);
                $maxId = max($maxId, $id);

                if ($norm === '' || $norm === $orig) {
                    continue;
                }

                $this->execute(sprintf(
                    'UPDATE IGNORE feeds SET feed_url = %s WHERE id = %d',
                    $this->quote($norm),
                    $id
                ));
            }
        } while (count($rows) === self::CHUNK);
    }

    private function dedupSubscriptionsByExactUrlUser(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_sub_canon_x`');
        $this->execute('CREATE TABLE `_sub_canon_x` (
            user         INT          NOT NULL,
            url          VARCHAR(512) NOT NULL,
            canonical_id INT          NOT NULL,
            INDEX (user, url(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('INSERT INTO `_sub_canon_x` (user, url, canonical_id)
            SELECT user, url, id FROM (
                SELECT id, user, url,
                       ROW_NUMBER() OVER (
                           PARTITION BY user, url
                           ORDER BY deleted ASC, changed DESC, id ASC
                       ) AS rn
                FROM subscriptions
            ) ranked
            WHERE rn = 1');

        $this->execute('DROP TABLE IF EXISTS `_sub_map_x`');
        $this->execute('CREATE TABLE `_sub_map_x` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_sub_map_x` (old_id, new_id)
            SELECT s.id, c.canonical_id
            FROM subscriptions s
            JOIN `_sub_canon_x` c ON c.user = s.user AND c.url = s.url
            WHERE s.id <> c.canonical_id');

        $this->remapSubscriptionReferences();

        $this->execute('DELETE s FROM subscriptions s
            JOIN `_sub_map_x` m ON m.old_id = s.id');

        $this->execute('DROP TABLE IF EXISTS `_sub_map_x`');
        $this->execute('DROP TABLE IF EXISTS `_sub_canon_x`');
    }

    private function normalizeAndRedupSubscriptions(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_sub_norm_x`');
        $this->execute('CREATE TABLE `_sub_norm_x` (
            old_id INT          NOT NULL PRIMARY KEY,
            user   INT          NOT NULL,
            norm   VARCHAR(512) NOT NULL,
            INDEX (user, norm(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $maxId = 0;
        do {
            $rows = $this->fetchAll(sprintf(
                'SELECT id, user, url FROM subscriptions WHERE id > %d ORDER BY id LIMIT %d',
                $maxId,
                self::CHUNK
            ));
            if (!$rows) {
                break;
            }

            $values = [];
            foreach ($rows as $row) {
                $id    = (int) $row['id'];
                $user  = (int) $row['user'];
                $maxId = max($maxId, $id);
                $norm  = Url::normalizeFeed((string) $row['url']);
                if ($norm === '') {
                    continue;
                }
                $values[] = sprintf('(%d, %d, %s)', $id, $user, $this->quote($norm));
            }

            if ($values) {
                $this->execute('INSERT INTO `_sub_norm_x` (old_id, user, norm) VALUES ' . implode(',', $values));
            }
        } while (count($rows) === self::CHUNK);

        $this->execute('DROP TABLE IF EXISTS `_sub_map_x`');
        $this->execute('CREATE TABLE `_sub_map_x` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_sub_map_x` (old_id, new_id)
            SELECT a.old_id, ranked.canonical_id
            FROM `_sub_norm_x` a
            JOIN (
                SELECT old_id AS canonical_id, user, norm FROM (
                    SELECT n.old_id, n.user, n.norm,
                           ROW_NUMBER() OVER (
                               PARTITION BY n.user, n.norm
                               ORDER BY s.deleted ASC, s.changed DESC, s.id ASC
                           ) AS rn
                    FROM `_sub_norm_x` n
                    JOIN subscriptions s ON s.id = n.old_id
                ) t WHERE rn = 1
            ) ranked ON ranked.user = a.user AND ranked.norm = a.norm
            WHERE a.old_id <> ranked.canonical_id');

        $this->remapSubscriptionReferences();

        $this->execute('DELETE s FROM subscriptions s
            JOIN `_sub_map_x` m ON m.old_id = s.id');

        $this->writeNormalizedSubscriptionUrls();

        $this->execute('DROP TABLE IF EXISTS `_sub_map_x`');
        $this->execute('DROP TABLE IF EXISTS `_sub_norm_x`');
    }

    private function remapSubscriptionReferences(): void
    {
        $this->execute('UPDATE IGNORE episodes_actions ea
            JOIN `_sub_map_x` m ON m.old_id = ea.subscription
            SET ea.subscription = m.new_id');
    }

    private function writeNormalizedSubscriptionUrls(): void
    {
        $maxId = 0;
        do {
            $rows = $this->fetchAll(sprintf(
                'SELECT id, url FROM subscriptions WHERE id > %d ORDER BY id LIMIT %d',
                $maxId,
                self::CHUNK
            ));
            if (!$rows) {
                break;
            }

            foreach ($rows as $row) {
                $id    = (int) $row['id'];
                $orig  = (string) $row['url'];
                $norm  = Url::normalizeFeed($orig);
                $maxId = max($maxId, $id);

                if ($norm === '' || $norm === $orig) {
                    continue;
                }

                $this->execute(sprintf(
                    'UPDATE IGNORE subscriptions SET url = %s WHERE id = %d',
                    $this->quote($norm),
                    $id
                ));
            }
        } while (count($rows) === self::CHUNK);

        $this->execute('UPDATE subscriptions s
            JOIN feeds f ON f.feed_url = s.url
            SET s.feed = f.id
            WHERE s.feed IS NULL OR s.feed <> f.id');

        $this->execute('UPDATE subscriptions s
            JOIN feed_aliases a ON a.url = s.url
            SET s.feed = a.feed_id
            WHERE s.feed IS NULL OR s.feed <> a.feed_id');
    }

    private function cleanupOrphanEpisodes(): void
    {
        $this->execute('DELETE e FROM episodes e
            LEFT JOIN feeds f ON f.id = e.feed
            WHERE f.id IS NULL');
    }

    private function ensureUniqueIndexes(): void
    {
        $this->execute('ALTER TABLE `feeds`
            DROP INDEX IF EXISTS `feed_url`,
            ADD UNIQUE INDEX `feed_url` (`feed_url`)');

        $this->execute('ALTER TABLE `subscriptions`
            DROP INDEX IF EXISTS `subscription_url`,
            ADD UNIQUE INDEX `subscription_url` (`url`, `user`)');
    }

    private function snapshot(): array
    {
        return [
            'feeds'         => (int) $this->fetchRow('SELECT COUNT(*) AS c FROM feeds')['c'],
            'subscriptions' => (int) $this->fetchRow('SELECT COUNT(*) AS c FROM subscriptions')['c'],
            'episodes'      => (int) $this->fetchRow('SELECT COUNT(*) AS c FROM episodes')['c'],
            'aliases'       => (int) $this->fetchRow('SELECT COUNT(*) AS c FROM feed_aliases')['c'],
        ];
    }

    private function report(array $before, array $after): void
    {
        $out = $this->getOutput();
        $out->writeln('');
        foreach ($before as $key => $b) {
            $a = $after[$key] ?? 0;
            $out->writeln(sprintf('  %-14s %10d -> %10d  (%+d)', $key . ':', $b, $a, $a - $b));
        }
        $out->writeln('');
    }

    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
