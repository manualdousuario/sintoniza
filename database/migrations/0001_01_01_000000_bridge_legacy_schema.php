<?php

use App\Support\Driver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bridge migration for existing Sintoniza (Phinx-era) databases.
 *
 * On a fresh database this migration is a no-op: the "create" migrations
 * that follow will build the normalized schema directly.
 *
 * On a legacy database (schema managed by Phinx), this migration transforms
 * the data in place to the Laravel-normalized schema:
 *
 *   users:              admin -> is_admin, active -> is_active, +remember_token,
 *                       +timestamps, password_reset_token* -> password_reset_tokens
 *   feeds:              last_fetch -> last_fetched_at, next_fetch_at (int -> datetime),
 *                       pubdate -> published_at, +timestamps
 *   feed_aliases:       created_at (int -> timestamp)
 *   subscriptions:      user -> user_id, feed -> feed_id, deleted -> deleted_at,
 *                       changed -> updated_at, +created_at
 *   devices:            user -> user_id, deviceid -> identifier, +timestamps
 *   episodes:           feed -> feed_id, pubdate -> published_at, +timestamps
 *   episodes_actions:   table renamed to episode_actions, FKs renamed to *_id,
 *                       changed -> changed_at, +timestamps, dedup UNIQUE rebuilt
 *
 * Every step is guarded by column existence/TYPE so the migration is fully
 * resumable: if it is interrupted (e.g. by a timeout on a huge table), just
 * run `php artisan migrate` again. Password hashes are NEVER touched, which
 * keeps existing gPodder auth tokens (sha1 of the stored hash) valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Driver::isMySql()) {
            // Legacy databases are always MySQL/MariaDB.
            return;
        }

        if (! Schema::hasTable('users')) {
            // Fresh install: nothing to bridge.
            return;
        }

        $legacy = Schema::hasColumn('subscriptions', 'user')
            || Schema::hasTable('episodes_actions')
            || Schema::hasColumn('users', 'admin')
            || Schema::hasColumn('feeds', 'last_fetch')
            || Schema::hasColumn('devices', 'user')
            || Schema::hasColumn('episodes', 'feed');

        if (! $legacy) {
            // Already normalized.
            return;
        }

        DB::statement("SET time_zone = '+00:00'");
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            $this->bridgeUsers();
            $this->bridgeFeeds();
            $this->bridgeFeedAliases();
            $this->bridgeSubscriptions();
            $this->bridgeDevices();
            $this->bridgeEpisodes();
            $this->bridgeEpisodeActions();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    public function down(): void
    {
        // The bridge is a one-way data transformation.
    }

    // ------------------------------------------------------------------ users

    private function bridgeUsers(): void
    {
        $this->renameColumnIfExists('users', 'admin', 'is_admin');
        $this->renameColumnIfExists('users', 'active', 'is_active');

        DB::statement('ALTER TABLE `users` MODIFY `name` VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });

        $this->addTimestamps('users');

        // Move outstanding password reset tokens to the Laravel table.
        if (Schema::hasColumn('users', 'password_reset_token')) {
            if (! Schema::hasTable('password_reset_tokens')) {
                Schema::create('password_reset_tokens', function (Blueprint $table) {
                    $table->string('email')->primary();
                    $table->string('token');
                    $table->timestamp('created_at')->nullable();
                });
            }

            $key = (string) config('app.key');
            if (str_starts_with($key, 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            foreach (DB::table('users')->whereNotNull('password_reset_token')->cursor() as $user) {
                DB::table('password_reset_tokens')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'token' => hash_hmac('sha256', $user->password_reset_token, $key),
                        'created_at' => $user->password_reset_token_expires_at
                            ? date('Y-m-d H:i:s', $user->password_reset_token_expires_at - 3600)
                            : date('Y-m-d H:i:s'),
                    ]
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['password_reset_token', 'password_reset_token_expires_at']);
            });
        }
    }

    // ------------------------------------------------------------------ feeds

    private function bridgeFeeds(): void
    {
        if (! Schema::hasTable('feeds')) {
            return;
        }

        $this->intToDateTime('feeds', 'last_fetch', 'last_fetched_at');
        $this->intToDateTime('feeds', 'next_fetch_at', 'next_fetch_at');
        $this->textToDateTime('feeds', 'pubdate', 'published_at');

        $this->addIndexIfMissing('feeds', 'feeds_next_fetch', '(`next_fetch_at`)');

        $this->addTimestamps('feeds', 'last_fetched_at');
    }

    // ------------------------------------------------------------ feed_aliases

    private function bridgeFeedAliases(): void
    {
        if (! Schema::hasTable('feed_aliases')) {
            return;
        }

        $type = $this->columnType('feed_aliases', 'created_at');
        $hasOld = $this->columnType('feed_aliases', 'created_at_old') !== null;

        if (! $hasOld && $type !== 'int') {
            return;
        }

        if (! $hasOld) {
            DB::statement('ALTER TABLE `feed_aliases` CHANGE `created_at` `created_at_old` INT(11) NOT NULL DEFAULT 0');
        }

        if ($this->columnType('feed_aliases', 'created_at') === null) {
            DB::statement('ALTER TABLE `feed_aliases` ADD `created_at` TIMESTAMP NULL DEFAULT NULL');
        }

        DB::statement('UPDATE `feed_aliases` SET `created_at` = FROM_UNIXTIME(`created_at_old`) WHERE `created_at_old` > 0');
        DB::statement('ALTER TABLE `feed_aliases` DROP `created_at_old`');
    }

    // ------------------------------------------------------------ subscriptions

    private function bridgeSubscriptions(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $this->dropForeignKeys('subscriptions');
        $this->dropIndexIfExists('subscriptions', 'subscriptions_user_deleted');

        $this->renameColumnIfExists('subscriptions', 'user', 'user_id');
        $this->renameColumnIfExists('subscriptions', 'feed', 'feed_id');

        // deleted (int flag) -> deleted_at (soft delete), keeping the change time
        if (Schema::hasColumn('subscriptions', 'deleted')) {
            if (! Schema::hasColumn('subscriptions', 'deleted_at')) {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable();
                });
            }

            $changedCol = $this->columnType('subscriptions', 'changed') !== null ? 'changed' : 'updated_at';
            DB::statement("UPDATE `subscriptions` SET `deleted_at` = COALESCE(`deleted_at`, FROM_UNIXTIME(`{$changedCol}`)) WHERE `deleted` = 1");

            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn(['deleted']);
            });
        }

        // changed (unix int) -> updated_at (sync cursor)
        $this->intToDateTime('subscriptions', 'changed', 'updated_at', keepNull: false);

        if (! Schema::hasColumn('subscriptions', 'created_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }
        DB::statement('UPDATE `subscriptions` SET `created_at` = `updated_at` WHERE `created_at` IS NULL');

        $this->addIndexIfMissing('subscriptions', 'subscriptions_user_deleted', '(`user_id`, `deleted_at`)');
        $this->addForeignKeyIfMissing('subscriptions', 'subscriptions_user_id_foreign',
            'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
        $this->addForeignKeyIfMissing('subscriptions', 'subscriptions_feed_id_foreign',
            'FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE SET NULL');
    }

    // ----------------------------------------------------------------- devices

    private function bridgeDevices(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        $this->dropForeignKeys('devices');

        $this->renameColumnIfExists('devices', 'user', 'user_id');
        $this->renameColumnIfExists('devices', 'deviceid', 'identifier');

        $this->addTimestamps('devices');

        $this->addForeignKeyIfMissing('devices', 'devices_user_id_foreign',
            'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
    }

    // ---------------------------------------------------------------- episodes

    private function bridgeEpisodes(): void
    {
        if (! Schema::hasTable('episodes')) {
            return;
        }

        $this->dropForeignKeys('episodes');
        $this->dropIndexIfExists('episodes', 'episodes_feed_pubdate');

        $this->renameColumnIfExists('episodes', 'feed', 'feed_id');

        $this->textToDateTime('episodes', 'pubdate', 'published_at');

        $this->addIndexIfMissing('episodes', 'episodes_feed_pubdate', '(`feed_id`, `published_at`)');

        $this->addTimestamps('episodes');

        $this->addForeignKeyIfMissing('episodes', 'episodes_feed_id_foreign',
            'FOREIGN KEY (`feed_id`) REFERENCES `feeds` (`id`) ON DELETE CASCADE');
    }

    // ---------------------------------------------------------- episode_actions

    private function bridgeEpisodeActions(): void
    {
        if (Schema::hasTable('episodes_actions') && ! Schema::hasTable('episode_actions')) {
            DB::statement('RENAME TABLE `episodes_actions` TO `episode_actions`');
        }

        if (! Schema::hasTable('episode_actions')) {
            return;
        }

        $this->dropForeignKeys('episode_actions');

        foreach (['episodes_actions_unique', 'episodes_idx', 'episodes_actions_user_changed',
            'episodes_actions_subscription_changed', 'episodes_actions_link'] as $index) {
            $this->dropIndexIfExists('episode_actions', $index);
        }

        $this->renameColumnIfExists('episode_actions', 'user', 'user_id');
        $this->renameColumnIfExists('episode_actions', 'subscription', 'subscription_id');
        $this->renameColumnIfExists('episode_actions', 'episode', 'episode_id');
        $this->renameColumnIfExists('episode_actions', 'device', 'device_id');

        $this->intToDateTime('episode_actions', 'changed', 'changed_at', keepNull: false);

        $this->addTimestamps('episode_actions', 'changed_at');

        // Rebuild the dedup UNIQUE key on the normalized columns.
        $this->addIndexIfMissing('episode_actions', 'episodes_actions_unique',
            '(`user_id`, `subscription_id`, `url`(255), `action`, `changed_at`)', unique: true);
        $this->addIndexIfMissing('episode_actions', 'episodes_idx', '(`user_id`, `action`, `changed_at`)');
        $this->addIndexIfMissing('episode_actions', 'episodes_actions_user_changed', '(`user_id`, `changed_at`)');
        $this->addIndexIfMissing('episode_actions', 'episodes_actions_subscription_changed', '(`subscription_id`, `changed_at`)');
        $this->addIndexIfMissing('episode_actions', 'episodes_actions_link', '(`episode_id`)');

        $this->addForeignKeyIfMissing('episode_actions', 'episode_actions_user_id_foreign',
            'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
        $this->addForeignKeyIfMissing('episode_actions', 'episode_actions_subscription_id_foreign',
            'FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE');
        $this->addForeignKeyIfMissing('episode_actions', 'episode_actions_episode_id_foreign',
            'FOREIGN KEY (`episode_id`) REFERENCES `episodes` (`id`) ON DELETE SET NULL');
        $this->addForeignKeyIfMissing('episode_actions', 'episode_actions_device_id_foreign',
            'FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE SET NULL');
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Convert a unix-timestamp INT column to a DATETIME column (same name by
     * default). Zero values become NULL. Resumable via column TYPE checks.
     */
    private function intToDateTime(string $table, string $from, string $to, bool $keepNull = true): void
    {
        $legacyCol = $from.'_legacy';
        $type = $this->columnType($table, $from);
        $hasLegacyCol = $this->columnType($table, $legacyCol) !== null;

        // Nothing to do: already converted (or column absent) and no leftovers
        if (! $hasLegacyCol && $type !== 'int') {
            return;
        }

        if (! $hasLegacyCol) {
            // $type === 'int': start the conversion
            if ($from === $to) {
                DB::statement("ALTER TABLE `{$table}` CHANGE `{$from}` `{$legacyCol}` INT(11) NOT NULL DEFAULT 0");
            } else {
                $legacyCol = $from;
            }
        }

        if ($this->columnType($table, $to) === null) {
            Schema::table($table, function (Blueprint $blueprint) use ($to) {
                $blueprint->dateTime($to)->nullable();
            });
        }

        DB::statement("UPDATE `{$table}` SET `{$to}` = FROM_UNIXTIME(`{$legacyCol}`) WHERE `{$legacyCol}` > 0");

        if (! $keepNull) {
            DB::statement("UPDATE `{$table}` SET `{$to}` = NOW() WHERE `{$to}` IS NULL");
        }

        if ($this->columnType($table, $legacyCol) !== null) {
            DB::statement("ALTER TABLE `{$table}` DROP `{$legacyCol}`");
        }
    }

    /**
     * Convert a legacy TEXT/VARCHAR datetime ('Y-m-d H:i:s UTC') column to a
     * real DATETIME column. Resumable via column TYPE checks.
     */
    private function textToDateTime(string $table, string $from, string $to): void
    {
        $type = $this->columnType($table, $from);

        if ($type === null || $type === 'datetime' || $type === 'timestamp') {
            return;
        }

        if ($this->columnType($table, $to) === null) {
            Schema::table($table, function (Blueprint $blueprint) use ($to) {
                $blueprint->dateTime($to)->nullable();
            });
        }

        DB::statement(
            "UPDATE `{$table}` SET `{$to}` = STR_TO_DATE(SUBSTRING(`{$from}`, 1, 19), '%Y-%m-%d %H:%i:%s')
             WHERE `{$from}` IS NOT NULL AND `{$from}` != ''"
        );

        Schema::table($table, function (Blueprint $blueprint) use ($from) {
            $blueprint->dropColumn([$from]);
        });
    }

    /**
     * Add Laravel timestamps to a legacy table, backfilling sensible values.
     */
    private function addTimestamps(string $table, ?string $createdFrom = null): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'created_at')) {
                $blueprint->timestamp('created_at')->nullable();
            }
            if (! Schema::hasColumn($table, 'updated_at')) {
                $blueprint->timestamp('updated_at')->nullable();
            }
        });

        if ($createdFrom && Schema::hasColumn($table, $createdFrom)) {
            DB::statement("UPDATE `{$table}` SET `created_at` = COALESCE(`{$createdFrom}`, NOW()) WHERE `created_at` IS NULL");
        }

        DB::statement("UPDATE `{$table}` SET `created_at` = NOW() WHERE `created_at` IS NULL");
        DB::statement("UPDATE `{$table}` SET `updated_at` = NOW() WHERE `updated_at` IS NULL");
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            DB::statement("ALTER TABLE `{$table}` RENAME COLUMN `{$from}` TO `{$to}`");
        }
    }

    private function columnType(string $table, string $column): ?string
    {
        $row = DB::selectOne(
            'SELECT DATA_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        );

        return $row->t ?? null;
    }

    /**
     * Drop every foreign key constraint of a table (legacy names vary).
     */
    private function dropForeignKeys(string $table): void
    {
        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME AS name FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, 'FOREIGN KEY']
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->name}`");
        }
    }

    private function addForeignKeyIfMissing(string $table, string $name, string $definition): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        if (! $exists) {
            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` {$definition}");
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (bool) DB::selectOne(
            'SELECT INDEX_NAME AS name FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index]
        );
    }

    private function addIndexIfMissing(string $table, string $index, string $columns, bool $unique = false): void
    {
        if (! $this->hasIndex($table, $index)) {
            $keyword = $unique ? 'UNIQUE' : 'INDEX';
            DB::statement("ALTER TABLE `{$table}` ADD {$keyword} `{$index}` {$columns}");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->hasIndex($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
