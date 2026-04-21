<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeAndDedupFeeds extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $this->execute('DROP TABLE IF EXISTS `_feed_canon`');
        $this->execute('CREATE TABLE `_feed_canon` (
            norm         VARCHAR(512) NOT NULL PRIMARY KEY,
            canonical_id INT          NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('INSERT INTO `_feed_canon` (norm, canonical_id)
            SELECT LOWER(TRIM(TRAILING "/" FROM feed_url)), MIN(id)
            FROM feeds
            GROUP BY LOWER(TRIM(TRAILING "/" FROM feed_url))');

        $this->execute('DROP TABLE IF EXISTS `_feed_map`');
        $this->execute('CREATE TABLE `_feed_map` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_feed_map` (old_id, new_id)
            SELECT f.id, c.canonical_id
            FROM feeds f
            JOIN `_feed_canon` c ON c.norm = LOWER(TRIM(TRAILING "/" FROM f.feed_url))
            WHERE f.id <> c.canonical_id');

        $this->execute('UPDATE subscriptions s
            JOIN `_feed_map` m ON m.old_id = s.feed
            SET s.feed = m.new_id');

        $this->execute('UPDATE IGNORE episodes e
            JOIN `_feed_map` m ON m.old_id = e.feed
            SET e.feed = m.new_id');

        $this->execute('DROP TABLE IF EXISTS `_episode_remap`');
        $this->execute('CREATE TABLE `_episode_remap` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_episode_remap` (old_id, new_id)
            SELECT e_old.id, e_new.id
            FROM episodes e_old
            JOIN `_feed_map` m ON m.old_id = e_old.feed
            JOIN episodes e_new ON e_new.feed = m.new_id
                               AND LEFT(e_new.media_url, 255) = LEFT(e_old.media_url, 255)');

        $this->execute('UPDATE episodes_actions ea
            JOIN `_episode_remap` er ON er.old_id = ea.episode
            SET ea.episode = er.new_id');

        $this->execute('DELETE f FROM feeds f
            JOIN `_feed_map` m ON m.old_id = f.id');

        $this->execute('UPDATE feeds
            SET feed_url = LOWER(TRIM(TRAILING "/" FROM feed_url))
            WHERE feed_url <> LOWER(TRIM(TRAILING "/" FROM feed_url))');

        $this->execute('DROP TABLE IF EXISTS `_feed_map`');
        $this->execute('DROP TABLE IF EXISTS `_feed_canon`');
        $this->execute('DROP TABLE IF EXISTS `_episode_remap`');

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
    }
}
