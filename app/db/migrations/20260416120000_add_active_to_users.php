<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddActiveToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE `users` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `admin`');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `users` DROP COLUMN `active`');
    }
}
