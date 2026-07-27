<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feeds')) {
            return;
        }

        Schema::create('feeds', function (Blueprint $table) {
            // INT AUTO_INCREMENT for parity with the legacy schema
            $table->integer('id', true);
            $table->string('feed_url', 512)->unique();
            $table->text('image_url')->nullable();
            $table->string('url', 512)->nullable();
            $table->string('language', 16)->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->dateTime('last_fetched_at')->nullable();
            $table->dateTime('next_fetch_at')->nullable()->index();
            $table->string('etag')->nullable();
            $table->string('last_modified', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeds');
    }
};
