<?php

namespace App\Filament\Widgets;

use App\Models\Quiz;
use App\Models\Test;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStudents = User::query()
            ->whereHas('enrolledCourses')
            ->count();

        $totalQuizzes = Quiz::query()->count();
        $publishedQuizzes = Quiz::query()->where('is_published', true)->count();

        $totalAttempts = Test::query()->count();

        $avgScore = DB::table('tests')
            ->join('quizzes', 'tests.quiz_id', '=', 'quizzes.id')
            ->selectRaw('
                AVG(CASE WHEN (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id) > 0 
                    THEN (tests.result * 100.0 / (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id)) 
                    ELSE 0 END) as avg_score
            ')
            ->value('avg_score');

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('Enrolled students')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Total Quizzes', $totalQuizzes)
                ->description($publishedQuizzes.' published')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Total Attempts', $totalAttempts)
                ->description('Quiz submissions')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Average Score', round($avgScore ?? 0, 1).'%')
                ->description('Across all attempts')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color(($avgScore ?? 0) >= 70 ? 'success' : (($avgScore ?? 0) >= 50 ? 'warning' : 'danger')),
        ];
    }
}
