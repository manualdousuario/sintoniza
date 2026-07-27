<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::create('subscriptions', function (Blueprint $table) {
            // INT AUTO_INCREMENT for parity with the legacy schema
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('feed_id')->nullable();
            $table->string('url', 512);
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['url', 'user_id'], 'subscription_url');
            $table->index('feed_id', 'subscription_feed');
            $table->index(['user_id', 'deleted_at'], 'subscriptions_user_deleted');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('feed_id')->references('id')->on('feeds')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
