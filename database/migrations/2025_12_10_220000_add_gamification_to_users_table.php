<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('xp_points')->default(0)->after('is_admin');
            $table->integer('level')->default(1)->after('xp_points');
            $table->integer('current_streak')->default(0)->after('level');
            $table->date('last_activity_date')->nullable()->after('current_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['xp_points', 'level', 'current_streak', 'last_activity_date']);
        });
    }
};
