<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeAndDedupSubscriptions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $this->execute('DROP TABLE IF EXISTS `_sub_canon`');
        $this->execute('CREATE TABLE `_sub_canon` (
            user         INT          NOT NULL,
            norm         VARCHAR(512) NOT NULL,
            canonical_id INT          NOT NULL,
            PRIMARY KEY (user, norm)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('INSERT INTO `_sub_canon` (user, norm, canonical_id)
            SELECT user, LOWER(TRIM(TRAILING "/" FROM url)), MIN(id)
            FROM subscriptions
            GROUP BY user, LOWER(TRIM(TRAILING "/" FROM url))');

        $this->execute('DROP TABLE IF EXISTS `_sub_map`');
        $this->execute('CREATE TABLE `_sub_map` (
            old_id INT NOT NULL PRIMARY KEY,
            new_id INT NOT NULL,
            INDEX (new_id)
        ) ENGINE=InnoDB');

        $this->execute('INSERT INTO `_sub_map` (old_id, new_id)
            SELECT s.id, c.canonical_id
            FROM subscriptions s
            JOIN `_sub_canon` c ON c.user = s.user
                              AND c.norm = LOWER(TRIM(TRAILING "/" FROM s.url))
            WHERE s.id <> c.canonical_id');

        $this->execute('UPDATE IGNORE episodes_actions ea
            JOIN `_sub_map` m ON m.old_id = ea.subscription
            SET ea.subscription = m.new_id');

        $this->execute('DELETE s FROM subscriptions s
            JOIN `_sub_map` m ON m.old_id = s.id');

        $this->execute('UPDATE subscriptions
            SET url = LOWER(TRIM(TRAILING "/" FROM url))
            WHERE url <> LOWER(TRIM(TRAILING "/" FROM url))');

        $this->execute('DROP TABLE IF EXISTS `_sub_map`');
        $this->execute('DROP TABLE IF EXISTS `_sub_canon`');

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
    }
}
