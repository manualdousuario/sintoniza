<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('episodes')) {
            return;
        }

        Schema::create('episodes', function (Blueprint $table) {
            // INT AUTO_INCREMENT for parity with the legacy schema
            $table->integer('id', true);
            $table->integer('feed_id');
            $table->text('media_url');
            $table->text('url')->nullable();
            $table->text('image_url')->nullable();
            $table->integer('duration')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['feed_id', 'published_at'], 'episodes_feed_pubdate');

            $table->foreign('feed_id')->references('id')->on('feeds')->cascadeOnDelete();
        });

        // One episode per media URL per feed. TEXT requires a prefix length on MySQL.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `episodes` ADD UNIQUE `episodes_unique` (`feed_id`, `media_url`(255))');
        } else {
            DB::statement('CREATE UNIQUE INDEX `episodes_unique` ON `episodes` (`feed_id`, `media_url`)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
