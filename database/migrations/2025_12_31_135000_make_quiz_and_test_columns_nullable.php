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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('attempts_allowed')->nullable()->change();
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->integer('result')->nullable()->change();
            $table->integer('time_spent')->nullable()->change();
        });

        Schema::table('test_answers', function (Blueprint $table) {
            $table->boolean('correct')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('attempts_allowed')->nullable(false)->default(3)->change();
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->integer('result')->nullable(false)->default(0)->change();
            $table->integer('time_spent')->nullable(false)->default(0)->change();
        });

        Schema::table('test_answers', function (Blueprint $table) {
            $table->boolean('correct')->nullable(false)->default(false)->change();
        });
    }
};
