<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DedupEpisodesActions extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $hasOld = $this->hasTable('episodes_actions_old');
        $hasNew = $this->hasTable('episodes_actions_new');

        if (!$hasOld) {
            if ($hasNew) {
                $this->execute('DROP TABLE `episodes_actions_new`');
            }

            $this->execute('CREATE TABLE `episodes_actions_new` LIKE `episodes_actions`');

            $this->execute('ALTER TABLE `episodes_actions_new`
                ADD UNIQUE INDEX `episodes_actions_unique` (`user`, `subscription`, `url`(255), `action`, `changed`)');

            $this->execute('INSERT IGNORE INTO `episodes_actions_new`
                SELECT * FROM `episodes_actions` ORDER BY `id`');

            $this->execute('RENAME TABLE
                `episodes_actions`     TO `episodes_actions_old`,
                `episodes_actions_new` TO `episodes_actions`');
        }

        // Drop old table first — releases the original FK constraint names
        // so we can recreate them on the swapped table without colliding.
        $this->execute('DROP TABLE IF EXISTS `episodes_actions_old`');

        $fkCount = (int) ($this->fetchRow(
            "SELECT COUNT(*) AS c
             FROM information_schema.referential_constraints
             WHERE constraint_schema = DATABASE()
               AND table_name = 'episodes_actions'"
        )['c'] ?? 0);

        if ($fkCount === 0) {
            $this->execute('ALTER TABLE `episodes_actions`
                ADD CONSTRAINT `episodes_actions_FK_0_0` FOREIGN KEY (`device`)       REFERENCES `devices`       (`id`) ON DELETE SET NULL,
                ADD CONSTRAINT `episodes_actions_FK_1_0` FOREIGN KEY (`episode`)      REFERENCES `episodes`      (`id`) ON DELETE SET NULL,
                ADD CONSTRAINT `episodes_actions_FK_2_0` FOREIGN KEY (`subscription`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
                ADD CONSTRAINT `episodes_actions_FK_3_0` FOREIGN KEY (`user`)         REFERENCES `users`         (`id`) ON DELETE CASCADE');
        }

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('ALTER TABLE `episodes_actions` DROP INDEX IF EXISTS `episodes_actions_unique`');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}
