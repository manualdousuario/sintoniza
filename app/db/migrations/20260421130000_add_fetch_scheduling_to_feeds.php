<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddFetchSchedulingToFeeds extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE `feeds`
            ADD COLUMN `next_fetch_at` INT(11)      NOT NULL DEFAULT 0  AFTER `last_fetch`,
            ADD COLUMN `etag`          VARCHAR(255) NULL     DEFAULT NULL AFTER `next_fetch_at`,
            ADD COLUMN `last_modified` VARCHAR(64)  NULL     DEFAULT NULL AFTER `etag`,
            DROP INDEX IF EXISTS `feeds_active_last_fetch`,
            ADD INDEX IF NOT EXISTS `feeds_active_next_fetch` (`active`, `next_fetch_at`)');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `feeds`
            DROP INDEX IF EXISTS `feeds_active_next_fetch`,
            ADD INDEX IF NOT EXISTS `feeds_active_last_fetch` (`active`, `last_fetch`),
            DROP COLUMN `last_modified`,
            DROP COLUMN `etag`,
            DROP COLUMN `next_fetch_at`');
    }
}
