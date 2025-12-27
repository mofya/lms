<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('correct_answer')->nullable()->after('type');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->nullable()->change();
        });

        // Ensure existing nullable records are treated as unpublished by default
        DB::table('quizzes')->whereNull('is_published')->update(['is_published' => false]);

        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('correct_answer');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('is_published')->nullable()->change();
        });
    }
};

