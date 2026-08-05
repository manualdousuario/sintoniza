<?php

use App\Support\Driver;
use App\Support\Url;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('episodes')) {
            return;
        }

        if (! Schema::hasColumn('episodes', 'media_url_normalized')) {
            Schema::table('episodes', function (Blueprint $table): void {
                $table->text('media_url_normalized')->nullable()->after('media_url');
            });
        }

        $this->backfill();

        if (! Schema::hasIndex('episodes', 'episodes_feed_media_normalized')) {
            if (Driver::isMySql()) {
                DB::statement('ALTER TABLE `episodes`
                    ADD INDEX `episodes_feed_media_normalized` (`feed_id`, `media_url_normalized`(255))');
            } else {
                Schema::table('episodes', function (Blueprint $table): void {
                    $table->index(['feed_id', 'media_url_normalized'], 'episodes_feed_media_normalized');
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('episodes') || ! Schema::hasColumn('episodes', 'media_url_normalized')) {
            return;
        }

        if (Schema::hasIndex('episodes', 'episodes_feed_media_normalized')) {
            if (Driver::isMySql()) {
                DB::statement('ALTER TABLE `episodes` DROP INDEX `episodes_feed_media_normalized`');
            } else {
                Schema::table('episodes', function (Blueprint $table): void {
                    $table->dropIndex('episodes_feed_media_normalized');
                });
            }
        }

        Schema::table('episodes', function (Blueprint $table): void {
            $table->dropColumn('media_url_normalized');
        });
    }

    private function backfill(): void
    {
        $expression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "TRIM(TRAILING '/' FROM LOWER(TRIM(media_url)))",
            'sqlite', 'pgsql' => "rtrim(lower(trim(media_url)), '/')",
            default => null,
        };

        if ($expression !== null) {
            DB::statement("UPDATE episodes SET media_url_normalized = {$expression} WHERE media_url_normalized IS NULL");

            return;
        }

        while (true) {
            $rows = DB::table('episodes')
                ->whereNull('media_url_normalized')
                ->orderBy('id')
                ->limit(1000)
                ->get(['id', 'media_url']);

            if ($rows->isEmpty()) {
                return;
            }

            foreach ($rows as $row) {
                DB::table('episodes')
                    ->where('id', $row->id)
                    ->update(['media_url_normalized' => Url::normalize((string) $row->media_url)]);
            }
        }
    }
};
