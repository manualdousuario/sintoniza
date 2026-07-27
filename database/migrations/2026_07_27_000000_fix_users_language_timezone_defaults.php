<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'language')) {
                $table->string('language', 5)->default('en')->change();
            }

            if (Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default('UTC')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'language')) {
                $table->string('language', 5)->default(null)->change();
            }

            if (Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default(null)->change();
            }
        });
    }
};
