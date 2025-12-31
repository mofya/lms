<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('questions_per_attempt')->nullable()->after('shuffle_options');
            $table->boolean('show_correct_answers')->default(true)->after('questions_per_attempt');
            $table->boolean('show_explanations')->default(true)->after('show_correct_answers');
            $table->string('feedback_timing')->default('after_submit')->after('show_explanations');
            $table->integer('passing_score')->nullable()->after('feedback_timing');
            $table->boolean('require_passing_to_proceed')->default(false)->after('passing_score');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'questions_per_attempt',
                'show_correct_answers',
                'show_explanations',
                'feedback_timing',
                'passing_score',
                'require_passing_to_proceed',
            ]);
        });
    }
};
