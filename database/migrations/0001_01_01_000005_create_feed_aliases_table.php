<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feed_aliases')) {
            return;
        }

        Schema::create('feed_aliases', function (Blueprint $table) {
            $table->string('url', 512)->primary();
            $table->integer('feed_id');
            $table->timestamp('created_at')->nullable();

            $table->foreign('feed_id')->references('id')->on('feeds')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_aliases');
    }
};
