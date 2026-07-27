<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('devices')) {
            return;
        }

        Schema::create('devices', function (Blueprint $table) {
            // INT AUTO_INCREMENT for parity with the legacy schema
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('identifier');
            $table->text('name')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();

            $table->unique(['identifier', 'user_id'], 'deviceid');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
