<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Sintoniza\Library\Url;

final class NormalizeV2AndDedup extends AbstractMigration
{
    private const CHUNK = 2000;

    public function up(): void
    {
        $countFeedsBefore = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM feeds')['c'];
        $countSubsBefore  = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM subscriptions')['c'];
        $countEpsBefore   = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM episodes')['c'];

        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $this->dedupFeeds();
        $this->dedupSubscriptions();
        $orphans = $this->deactivateOrphanFeeds();

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

        $countFeedsAfter = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM feeds')['c'];
        $countSubsAfter  = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM subscriptions')['c'];
        $countEpsAfter   = (int) $this->fetchRow('SELECT COUNT(*) AS c FROM episodes')['c'];

        $this->report($countFeedsBefore, $countFeedsAfter, $countSubsBefore, $countSubsAfter, $countEpsBefore, $countEpsAfter, $orphans);
    }

    public function down(): void
    {
    }

    private function dedupFeeds(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_feed_canon_v2`');
        $this->execute('CREATE TABLE `_feed_canon_v2` (
            old_id INT          NOT NULL PRIMARY KEY,
            norm   VARCHAR(512) NOT NULL,
            INDEX (norm)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->normalizeChunked(
            'SELECT id, feed_url FROM feeds WHERE id > ? ORDER BY id LIMIT ' . self::CHUNK,
            'INSERT INTO `_feed_canon_v2` (old_id, norm) VALUES ',
            fn(array $row) => [(int) $row['id'], Url::normalizeFeed((string) $row['feed_url'])]
        );

        $this->execute('DROP TABLE IF EXISTS `_feed_map_v2`');
        $this->execute('CREATE TABLE `_feed_map_v2` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_feed_map_v2` (old_id, new_id)
            SELECT c.old_id, MIN(c2.old_id)
            FROM `_feed_canon_v2` c
            JOIN `_feed_canon_v2` c2 ON c2.norm = c.norm
            GROUP BY c.old_id
            HAVING c.old_id <> MIN(c2.old_id)');

        $this->execute('UPDATE subscriptions s
            JOIN `_feed_map_v2` m ON m.old_id = s.feed
            SET s.feed = m.new_id');

        $this->execute('UPDATE IGNORE episodes e
            JOIN `_feed_map_v2` m ON m.old_id = e.feed
            SET e.feed = m.new_id');

        $this->execute('DROP TABLE IF EXISTS `_episode_remap_v2`');
        $this->execute('CREATE TABLE `_episode_remap_v2` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_episode_remap_v2` (old_id, new_id)
            SELECT e_old.id, e_new.id
            FROM episodes e_old
            JOIN `_feed_map_v2` m  ON m.old_id  = e_old.feed
            JOIN episodes      e_new ON e_new.feed = m.new_id
                                    AND LEFT(e_new.media_url, 255) = LEFT(e_old.media_url, 255)');

        $this->execute('UPDATE episodes_actions ea
            JOIN `_episode_remap_v2` er ON er.old_id = ea.episode
            SET ea.episode = er.new_id');

        $this->execute('UPDATE IGNORE feed_aliases a
            JOIN `_feed_map_v2` m ON m.old_id = a.feed_id
            SET a.feed_id = m.new_id');

        $this->execute('DELETE f FROM feeds f
            JOIN `_feed_map_v2` m ON m.old_id = f.id');

        $this->writeNormalizedFeedUrls();

        $this->execute('INSERT IGNORE INTO feed_aliases (url, feed_id, created_at)
            SELECT c.norm, m.new_id, UNIX_TIMESTAMP()
            FROM `_feed_map_v2` m
            JOIN `_feed_canon_v2` c ON c.old_id = m.old_id');

        $this->execute('DROP TABLE IF EXISTS `_feed_map_v2`');
        $this->execute('DROP TABLE IF EXISTS `_feed_canon_v2`');
        $this->execute('DROP TABLE IF EXISTS `_episode_remap_v2`');
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
                $id   = (int) $row['id'];
                $orig = (string) $row['feed_url'];
                $norm = Url::normalizeFeed($orig);

                if ($norm === '' || $norm === $orig) {
                    $maxId = $id;
                    continue;
                }

                $this->execute(sprintf(
                    "UPDATE IGNORE feeds SET feed_url = %s WHERE id = %d",
                    $this->getAdapter()->getConnection()->quote($norm),
                    $id
                ));

                $maxId = $id;
            }
        } while (count($rows) === self::CHUNK);
    }

    private function dedupSubscriptions(): void
    {
        $this->execute('DROP TABLE IF EXISTS `_sub_canon_v2`');
        $this->execute('CREATE TABLE `_sub_canon_v2` (
            old_id INT          NOT NULL PRIMARY KEY,
            user   INT          NOT NULL,
            norm   VARCHAR(512) NOT NULL,
            INDEX (user, norm)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->normalizeChunked(
            'SELECT id, user, url FROM subscriptions WHERE id > ? ORDER BY id LIMIT ' . self::CHUNK,
            'INSERT INTO `_sub_canon_v2` (old_id, user, norm) VALUES ',
            fn(array $row) => [(int) $row['id'], (int) $row['user'], Url::normalizeFeed((string) $row['url'])]
        );

        $this->execute('DROP TABLE IF EXISTS `_sub_map_v2`');
        $this->execute('CREATE TABLE `_sub_map_v2` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_sub_map_v2` (old_id, new_id)
            SELECT c.old_id, MIN(c2.old_id)
            FROM `_sub_canon_v2` c
            JOIN `_sub_canon_v2` c2 ON c2.user = c.user AND c2.norm = c.norm
            GROUP BY c.old_id
            HAVING c.old_id <> MIN(c2.old_id)');

        $this->execute('UPDATE IGNORE episodes_actions ea
            JOIN `_sub_map_v2` m ON m.old_id = ea.subscription
            SET ea.subscription = m.new_id');

        $this->execute('DELETE s FROM subscriptions s
            JOIN `_sub_map_v2` m ON m.old_id = s.id');

        $this->writeNormalizedSubscriptionUrls();

        $this->execute('UPDATE subscriptions s
            JOIN feeds f ON f.feed_url = s.url
            SET s.feed = f.id
            WHERE s.feed IS NULL OR s.feed <> f.id');

        $this->execute('UPDATE subscriptions s
            JOIN feed_aliases a ON a.url = s.url
            SET s.feed = a.feed_id
            WHERE s.feed IS NULL OR s.feed <> a.feed_id');

        $this->execute('DROP TABLE IF EXISTS `_sub_map_v2`');
        $this->execute('DROP TABLE IF EXISTS `_sub_canon_v2`');
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
                $id   = (int) $row['id'];
                $orig = (string) $row['url'];
                $norm = Url::normalizeFeed($orig);

                if ($norm === '' || $norm === $orig) {
                    $maxId = $id;
                    continue;
                }

                $this->execute(sprintf(
                    "UPDATE subscriptions SET url = %s WHERE id = %d",
                    $this->getAdapter()->getConnection()->quote($norm),
                    $id
                ));

                $maxId = $id;
            }
        } while (count($rows) === self::CHUNK);
    }

    private function deactivateOrphanFeeds(): int
    {
        $candidates = (int) $this->fetchRow(
            'SELECT COUNT(*) AS c FROM feeds f
                LEFT JOIN subscriptions s ON s.feed = f.id AND s.deleted = 0
                WHERE s.id IS NULL AND f.active = 1'
        )['c'];

        if ($candidates > 0) {
            $this->execute('UPDATE feeds f
                LEFT JOIN subscriptions s ON s.feed = f.id AND s.deleted = 0
                SET f.active = 0
                WHERE s.id IS NULL AND f.active = 1');
        }

        return $candidates;
    }

    private function normalizeChunked(string $selectSql, string $insertPrefix, callable $mapper): void
    {
        $maxId = 0;

        do {
            $rows = $this->fetchAll(str_replace('?', (string) $maxId, $selectSql));
            if (!$rows) {
                break;
            }

            $values = [];
            foreach ($rows as $row) {
                $tuple = $mapper($row);
                $maxId = max($maxId, (int) $tuple[0]);

                $escaped = [];
                foreach ($tuple as $v) {
                    $escaped[] = is_int($v)
                        ? (string) $v
                        : $this->getAdapter()->getConnection()->quote((string) $v);
                }
                $values[] = '(' . implode(',', $escaped) . ')';
            }

            if ($values) {
                $this->execute($insertPrefix . implode(',', $values));
            }
        } while (count($rows) === self::CHUNK);
    }

    private function report(int $fb, int $fa, int $sb, int $sa, int $eb, int $ea, int $orphans): void
    {
        $out = $this->getOutput();
        $out->writeln('');
        $out->writeln(sprintf('  Feeds:         %d -> %d  (%+d)', $fb, $fa, $fa - $fb));
        $out->writeln(sprintf('  Subscriptions: %d -> %d  (%+d)', $sb, $sa, $sa - $sb));
        $out->writeln(sprintf('  Episodes:      %d -> %d  (%+d)', $eb, $ea, $ea - $eb));
        $out->writeln(sprintf('  Orphan feeds deactivated: %d', $orphans));
        $out->writeln('');
    }
}
