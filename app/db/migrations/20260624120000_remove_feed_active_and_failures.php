<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveFeedActiveAndFailures extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE `feeds`
            DROP INDEX IF EXISTS `feeds_active_next_fetch`,
            ADD INDEX IF NOT EXISTS `feeds_next_fetch` (`next_fetch_at`),
            DROP COLUMN `active`,
            DROP COLUMN `fetch_failures`');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `feeds`
            ADD COLUMN `fetch_failures` INT(11)     NOT NULL DEFAULT 0 AFTER `last_fetch`,
            ADD COLUMN `active`         TINYINT(1)   NOT NULL DEFAULT 1 AFTER `fetch_failures`,
            DROP INDEX IF EXISTS `feeds_next_fetch`,
            ADD INDEX IF NOT EXISTS `feeds_active_next_fetch` (`active`, `next_fetch_at`)');
    }
}
