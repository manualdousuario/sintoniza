<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropRedundantSubscriptionIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('ALTER TABLE `episodes_actions`
            DROP INDEX IF EXISTS `episodes_actions_FK_2_0`');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('ALTER TABLE `episodes_actions`
            ADD INDEX IF NOT EXISTS `episodes_actions_FK_2_0` (`subscription`)');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}
