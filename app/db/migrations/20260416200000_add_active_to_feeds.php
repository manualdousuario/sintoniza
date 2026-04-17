<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddActiveToFeeds extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE `feeds`
            ADD COLUMN `fetch_failures` INT(11) NOT NULL DEFAULT 0 AFTER `last_fetch`,
            ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `fetch_failures`');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `feeds` DROP COLUMN `active`, DROP COLUMN `fetch_failures`');
    }
}
