<?php

namespace App\Filament\Student\Widgets;

use App\Models\Test;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $userId = Auth::id();

        $stats = Test::query()
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_attempts,
                COUNT(DISTINCT quiz_id) as quizzes_taken
            ')
            ->first();

        $scoreStats = DB::table('tests')
            ->join('quizzes', 'tests.quiz_id', '=', 'quizzes.id')
            ->where('tests.user_id', $userId)
            ->selectRaw('
                AVG(CASE WHEN (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id) > 0 
                    THEN (tests.result * 100.0 / (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id)) 
                    ELSE 0 END) as avg_score,
                SUM(CASE WHEN (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id) > 0 
                    AND (tests.result * 100.0 / (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id)) >= 70 
                    THEN 1 ELSE 0 END) as passed_count
            ')
            ->first();

        $avgScore = round($scoreStats->avg_score ?? 0, 1);
        $passRate = $stats->total_attempts > 0
            ? round((($scoreStats->passed_count ?? 0) / $stats->total_attempts) * 100, 1)
            : 0;

        return [
            Stat::make('Quizzes Taken', $stats->quizzes_taken ?? 0)
                ->description('Unique quizzes attempted')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Total Attempts', $stats->total_attempts ?? 0)
                ->description('All quiz attempts')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('info'),

            Stat::make('Average Score', $avgScore.'%')
                ->description('Across all attempts')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgScore >= 70 ? 'success' : ($avgScore >= 50 ? 'warning' : 'danger')),

            Stat::make('Pass Rate', $passRate.'%')
                ->description('Attempts ≥ 70%')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($passRate >= 70 ? 'success' : ($passRate >= 50 ? 'warning' : 'danger')),
        ];
    }
}
