<?php

use App\Support\Driver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('episode_actions') || ! Schema::hasIndex('episode_actions', 'episodes_actions_url')) {
            return;
        }

        if (Driver::isMySql()) {
            DB::statement('ALTER TABLE `episode_actions` DROP INDEX `episodes_actions_url`');

            return;
        }

        Schema::table('episode_actions', function (Blueprint $table): void {
            $table->dropIndex('episodes_actions_url');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('episode_actions') || Schema::hasIndex('episode_actions', 'episodes_actions_url')) {
            return;
        }

        if (Driver::isMySql()) {
            DB::statement('ALTER TABLE `episode_actions` ADD INDEX `episodes_actions_url` (`url`(255))');

            return;
        }

        Schema::table('episode_actions', function (Blueprint $table): void {
            $table->index('url', 'episodes_actions_url');
        });
    }
};
