<?php

namespace App\Filament\Student\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CourseQuizPerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Performance by Course';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => \App\Models\Course::query()
                    ->select([
                        'courses.id',
                        'courses.title',
                    ])
                    ->join('quizzes', 'quizzes.course_id', '=', 'courses.id')
                    ->join('tests', 'tests.quiz_id', '=', 'quizzes.id')
                    ->where('tests.user_id', Auth::id())
                    ->groupBy('courses.id', 'courses.title')
                    ->selectRaw('COUNT(tests.id) as attempts_count')
                    ->selectRaw('AVG(tests.result) as avg_correct')
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Course')
                    ->searchable(),

                TextColumn::make('attempts_count')
                    ->label('Attempts')
                    ->alignCenter(),

                TextColumn::make('performance')
                    ->label('Avg Performance')
                    ->getStateUsing(function ($record): string {
                        $avgScore = $this->calculateCoursePerformance($record->id);

                        return $avgScore.'%';
                    })
                    ->badge()
                    ->color(function ($record): string {
                        $avgScore = $this->calculateCoursePerformance($record->id);

                        return $avgScore >= 70 ? 'success' : ($avgScore >= 50 ? 'warning' : 'danger');
                    }),

                TextColumn::make('progress_bar')
                    ->label('Progress')
                    ->getStateUsing(function ($record): int {
                        return $this->calculateCoursePerformance($record->id);
                    })
                    ->formatStateUsing(fn () => '')
                    ->extraAttributes(function ($record): array {
                        $percentage = $this->calculateCoursePerformance($record->id);
                        $color = $percentage >= 70 ? 'bg-emerald-500' : ($percentage >= 50 ? 'bg-amber-500' : 'bg-red-500');

                        return [
                            'class' => 'relative',
                            'style' => 'min-width: 120px;',
                            'x-data' => '{}',
                            'x-init' => "
                                \$el.innerHTML = `
                                    <div class='flex items-center gap-2'>
                                        <div class='h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700'>
                                            <div class='h-full rounded-full {$color}' style='width: {$percentage}%'></div>
                                        </div>
                                        <span class='text-sm font-medium'>{$percentage}%</span>
                                    </div>
                                `;
                            ",
                        ];
                    }),
            ])
            ->paginated(false);
    }

    private function calculateCoursePerformance(int $courseId): int
    {
        $result = DB::table('tests')
            ->join('quizzes', 'tests.quiz_id', '=', 'quizzes.id')
            ->join('question_quiz', 'question_quiz.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.course_id', $courseId)
            ->where('tests.user_id', Auth::id())
            ->selectRaw('
                SUM(tests.result) as total_correct,
                COUNT(DISTINCT tests.id) as attempt_count
            ')
            ->first();

        if (! $result || $result->attempt_count == 0) {
            return 0;
        }

        $totalQuestions = DB::table('tests')
            ->join('quizzes', 'tests.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.course_id', $courseId)
            ->where('tests.user_id', Auth::id())
            ->selectRaw('
                (SELECT COUNT(*) FROM question_quiz WHERE quiz_id = quizzes.id) as q_count
            ')
            ->get()
            ->sum('q_count');

        if ($totalQuestions == 0) {
            return 0;
        }

        return (int) round(($result->total_correct / $totalQuestions) * 100);
    }
}
