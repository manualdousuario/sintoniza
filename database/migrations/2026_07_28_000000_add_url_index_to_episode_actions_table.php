<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('episode_actions') || Schema::hasIndex('episode_actions', 'episodes_actions_url')) {
            return;
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `episode_actions` ADD INDEX `episodes_actions_url` (`url`(255))');
        } else {
            DB::statement('CREATE INDEX `episodes_actions_url` ON `episode_actions` (`url`)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('episode_actions') || ! Schema::hasIndex('episode_actions', 'episodes_actions_url')) {
            return;
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `episode_actions` DROP INDEX `episodes_actions_url`');
        } else {
            DB::statement('DROP INDEX `episodes_actions_url`');
        }
    }
};
