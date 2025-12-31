<?php

use App\Enums\NavigatorPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('show_one_question_at_a_time')->default(true)->after('attempts_allowed');
            $table->string('navigator_position')->default(NavigatorPosition::Bottom->value)->after('show_one_question_at_a_time');
            $table->boolean('shuffle_questions')->default(true)->after('navigator_position');
            $table->boolean('shuffle_options')->default(true)->after('shuffle_questions');
            $table->boolean('show_progress_bar')->default(true)->after('shuffle_options');
            $table->boolean('allow_question_navigation')->default(true)->after('show_progress_bar');
            $table->boolean('auto_advance_on_answer')->default(false)->after('allow_question_navigation');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'show_one_question_at_a_time',
                'navigator_position',
                'shuffle_questions',
                'shuffle_options',
                'show_progress_bar',
                'allow_question_navigation',
                'auto_advance_on_answer',
            ]);
        });
    }
};
