<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFeedAliasesAndCanonical extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `feed_aliases` (
            `url`        VARCHAR(512) NOT NULL,
            `feed_id`    INT(11)      NOT NULL,
            `created_at` INT(11)      NOT NULL DEFAULT 0,
            PRIMARY KEY (`url`),
            INDEX `feed_aliases_feed_id` (`feed_id`),
            CONSTRAINT `feed_aliases_feed_fk`
                FOREIGN KEY (`feed_id`) REFERENCES `feeds`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `feed_aliases`');
    }
}
