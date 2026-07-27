<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('episode_actions')) {
            return;
        }

        Schema::create('episode_actions', function (Blueprint $table) {
            // INT AUTO_INCREMENT for parity with the legacy schema
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('subscription_id');
            $table->integer('episode_id')->nullable();
            $table->integer('device_id')->nullable();
            $table->text('url');
            $table->dateTime('changed_at');
            $table->string('action', 32);
            $table->text('data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action', 'changed_at'], 'episodes_idx');
            $table->index(['user_id', 'changed_at'], 'episodes_actions_user_changed');
            $table->index(['subscription_id', 'changed_at'], 'episodes_actions_subscription_changed');
            $table->index('episode_id', 'episodes_actions_link');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreign('episode_id')->references('id')->on('episodes')->nullOnDelete();
            $table->foreign('device_id')->references('id')->on('devices')->nullOnDelete();
        });

        // Dedup key: identical actions at identical timestamps are dropped
        // (INSERT IGNORE semantics from the legacy app). TEXT needs prefix on MySQL.
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `episode_actions`
                ADD UNIQUE `episodes_actions_unique` (`user_id`, `subscription_id`, `url`(255), `action`, `changed_at`)');
        } else {
            DB::statement('CREATE UNIQUE INDEX `episodes_actions_unique`
                ON `episode_actions` (`user_id`, `subscription_id`, `url`, `action`, `changed_at`)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_actions');
    }
};
