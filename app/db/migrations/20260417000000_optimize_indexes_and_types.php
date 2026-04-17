<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OptimizeIndexesAndTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE `feeds`
            MODIFY `feed_url` VARCHAR(512) NOT NULL,
            MODIFY `url`      VARCHAR(512) NULL DEFAULT NULL,
            MODIFY `language` VARCHAR(16)  NULL DEFAULT NULL,
            MODIFY `pubdate`  VARCHAR(32)  NULL DEFAULT NULL,
            DROP INDEX IF EXISTS `feed_url`,
            ADD UNIQUE INDEX IF NOT EXISTS `feed_url` (`feed_url`),
            ADD INDEX IF NOT EXISTS `feeds_active_last_fetch` (`active`, `last_fetch`)');

        $this->execute('ALTER TABLE `subscriptions`
            MODIFY `url` VARCHAR(512) NOT NULL,
            DROP INDEX IF EXISTS `subscription_url`,
            ADD UNIQUE INDEX IF NOT EXISTS `subscription_url` (`url`, `user`),
            ADD INDEX IF NOT EXISTS `subscriptions_user_deleted` (`user`, `deleted`)');

        $this->execute('ALTER TABLE `devices`
            MODIFY `deviceid` VARCHAR(255) NOT NULL,
            DROP INDEX IF EXISTS `deviceid`,
            ADD UNIQUE INDEX IF NOT EXISTS `deviceid` (`deviceid`, `user`)');

        $this->execute('ALTER TABLE `episodes`
            ADD INDEX IF NOT EXISTS `episodes_feed_pubdate` (`feed`, `pubdate`(32))');

        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('ALTER TABLE `episodes_actions`
            MODIFY `action` VARCHAR(32) NOT NULL,
            DROP INDEX IF EXISTS `episodes_idx`,
            ADD INDEX IF NOT EXISTS `episodes_idx` (`user`, `action`, `changed`),
            ADD INDEX IF NOT EXISTS `episodes_actions_subscription_changed` (`subscription`, `changed`)');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('ALTER TABLE `episodes_actions`
            DROP INDEX IF EXISTS `episodes_actions_subscription_changed`,
            DROP INDEX IF EXISTS `episodes_idx`,
            ADD INDEX IF NOT EXISTS `episodes_idx` (`user`, `action`(255), `changed`),
            MODIFY `action` TEXT NOT NULL');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');

        $this->execute('ALTER TABLE `episodes`
            DROP INDEX IF EXISTS `episodes_feed_pubdate`');

        $this->execute('ALTER TABLE `devices`
            DROP INDEX IF EXISTS `deviceid`,
            ADD UNIQUE INDEX IF NOT EXISTS `deviceid` (`deviceid`(255), `user`),
            MODIFY `deviceid` TEXT NOT NULL');

        $this->execute('ALTER TABLE `subscriptions`
            DROP INDEX IF EXISTS `subscriptions_user_deleted`,
            DROP INDEX IF EXISTS `subscription_url`,
            ADD UNIQUE INDEX IF NOT EXISTS `subscription_url` (`url`(255), `user`),
            MODIFY `url` TEXT NOT NULL');

        $this->execute('ALTER TABLE `feeds`
            DROP INDEX IF EXISTS `feeds_active_last_fetch`,
            DROP INDEX IF EXISTS `feed_url`,
            ADD UNIQUE INDEX IF NOT EXISTS `feed_url` (`feed_url`(255)),
            MODIFY `pubdate`  TEXT NULL DEFAULT NULL,
            MODIFY `language` TEXT NULL DEFAULT NULL,
            MODIFY `url`      TEXT NULL DEFAULT NULL,
            MODIFY `feed_url` TEXT NOT NULL');
    }
}
