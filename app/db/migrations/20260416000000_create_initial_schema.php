<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');

        $this->execute('CREATE TABLE IF NOT EXISTS `users` (
            `id`                              INT(11)       NOT NULL AUTO_INCREMENT,
            `name`                            TEXT          NOT NULL,
            `password`                        TEXT          NOT NULL,
            `admin`                           TINYINT(1)    NOT NULL DEFAULT 0,
            `email`                           VARCHAR(255)  NOT NULL,
            `language`                        VARCHAR(5)    NOT NULL DEFAULT \'en\',
            `timezone`                        VARCHAR(50)   NOT NULL DEFAULT \'UTC\',
            `password_reset_token`            VARCHAR(255)  NULL DEFAULT NULL,
            `password_reset_token_expires_at` INT(11)       NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `users_name` (`name`(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('CREATE TABLE IF NOT EXISTS `feeds` (
            `id`          INT(11)  NOT NULL AUTO_INCREMENT,
            `feed_url`    TEXT     NOT NULL,
            `image_url`   TEXT     NULL DEFAULT NULL,
            `url`         TEXT     NULL DEFAULT NULL,
            `language`    TEXT     NULL DEFAULT NULL,
            `title`       TEXT     NULL DEFAULT NULL,
            `description` TEXT     NULL DEFAULT NULL,
            `pubdate`     TEXT     NULL DEFAULT NULL,
            `last_fetch`  INT(11)  NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `feed_url` (`feed_url`(255))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('CREATE TABLE IF NOT EXISTS `subscriptions` (
            `id`      INT(11)  NOT NULL AUTO_INCREMENT,
            `user`    INT(11)  NOT NULL,
            `feed`    INT(11)  NULL DEFAULT NULL,
            `url`     TEXT     NOT NULL,
            `deleted` INT(11)  NOT NULL DEFAULT 0,
            `changed` INT(11)  NOT NULL,
            `data`    TEXT     NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `subscription_url` (`url`(255), `user`),
            INDEX `subscription_feed` (`feed`),
            INDEX `subscriptions_FK_1_0` (`user`),
            CONSTRAINT `subscriptions_FK_0_0` FOREIGN KEY (`feed`)  REFERENCES `feeds` (`id`) ON DELETE SET NULL,
            CONSTRAINT `subscriptions_FK_1_0` FOREIGN KEY (`user`)  REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('CREATE TABLE IF NOT EXISTS `devices` (
            `id`       INT(11)  NOT NULL AUTO_INCREMENT,
            `user`     INT(11)  NOT NULL,
            `deviceid` TEXT     NOT NULL,
            `name`     TEXT     NULL DEFAULT NULL,
            `data`     TEXT     NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `deviceid` (`deviceid`(255), `user`),
            INDEX `devices_FK_0_0` (`user`),
            CONSTRAINT `devices_FK_0_0` FOREIGN KEY (`user`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('CREATE TABLE IF NOT EXISTS `episodes` (
            `id`          INT(11)  NOT NULL AUTO_INCREMENT,
            `feed`        INT(11)  NOT NULL,
            `media_url`   TEXT     NOT NULL,
            `url`         TEXT     NULL DEFAULT NULL,
            `image_url`   TEXT     NULL DEFAULT NULL,
            `duration`    INT(11)  NULL DEFAULT NULL,
            `title`       TEXT     NULL DEFAULT NULL,
            `description` TEXT     NULL DEFAULT NULL,
            `pubdate`     TEXT     NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `episodes_unique` (`feed`, `media_url`(255)),
            CONSTRAINT `episodes_FK_0_0` FOREIGN KEY (`feed`) REFERENCES `feeds` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('CREATE TABLE IF NOT EXISTS `episodes_actions` (
            `id`           INT(11)  NOT NULL AUTO_INCREMENT,
            `user`         INT(11)  NOT NULL,
            `subscription` INT(11)  NOT NULL,
            `episode`      INT(11)  NULL DEFAULT NULL,
            `device`       INT(11)  NULL DEFAULT NULL,
            `url`          TEXT     NOT NULL,
            `changed`      INT(11)  NOT NULL,
            `action`       TEXT     NOT NULL,
            `data`         TEXT     NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            INDEX `episodes_actions_link`   (`episode`),
            INDEX `episodes_idx`            (`user`, `action`(255), `changed`),
            INDEX `episodes_actions_FK_0_0` (`device`),
            INDEX `episodes_actions_FK_2_0` (`subscription`),
            CONSTRAINT `episodes_actions_FK_0_0` FOREIGN KEY (`device`)       REFERENCES `devices`       (`id`) ON DELETE SET NULL,
            CONSTRAINT `episodes_actions_FK_1_0` FOREIGN KEY (`episode`)      REFERENCES `episodes`      (`id`) ON DELETE SET NULL,
            CONSTRAINT `episodes_actions_FK_2_0` FOREIGN KEY (`subscription`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
            CONSTRAINT `episodes_actions_FK_3_0` FOREIGN KEY (`user`)         REFERENCES `users`         (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS = 0');
        $this->execute('DROP TABLE IF EXISTS `episodes_actions`');
        $this->execute('DROP TABLE IF EXISTS `episodes`');
        $this->execute('DROP TABLE IF EXISTS `devices`');
        $this->execute('DROP TABLE IF EXISTS `subscriptions`');
        $this->execute('DROP TABLE IF EXISTS `feeds`');
        $this->execute('DROP TABLE IF EXISTS `users`');
        $this->execute('SET FOREIGN_KEY_CHECKS = 1');
    }
}
