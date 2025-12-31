<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('attempt_number');
            $table->timestamp('submitted_at')->nullable()->after('started_at');
            $table->integer('correct_count')->nullable()->after('submitted_at');
            $table->integer('wrong_count')->nullable()->after('correct_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'submitted_at', 'correct_count', 'wrong_count']);
        });
    }
};
