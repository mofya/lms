<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use stdClass;
use Filament\Tables;
use App\Models\User;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;

class Leaderboard extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('is_admin', false)
                    ->select([
                        'users.id',
                        'users.name',
                        'users.xp_points',
                        'users.level',
                        'users.current_streak',
                        DB::raw('sum(tests.result) as correct'),
                        DB::raw('sum(tests.time_spent) as time_spent'),
                        DB::raw("(SELECT COUNT(question_quiz.question_id)
                            FROM quizzes
                            JOIN question_quiz ON quizzes.id = question_quiz.quiz_id
                            WHERE quizzes.is_published = 1
                        ) as total_questions")
                    ])
                    ->leftJoin('tests', 'users.id', '=', 'tests.user_id')
                    ->groupBy('users.id', 'users.name', 'users.xp_points', 'users.level', 'users.current_streak')
                    ->orderBy('xp_points', 'desc')
                    ->orderBy('level', 'desc')
                    ->take(10)
            )
            ->columns([
                TextColumn::make('place')
                    ->state(
                        static function (stdClass $rowLoop): int {
                            return (int) $rowLoop->iteration;
                        }
                    ),
                TextColumn::make('name')
                    ->weight('bold'),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color('success'),
                TextColumn::make('xp_points')
                    ->label('XP')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable(),
                TextColumn::make('current_streak')
                    ->label('Streak')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} days" : '-')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('correctAnswers')
                    ->label('Quiz Score')
                    ->state(function (User $record): string {
                        if (!$record->correct) return '-';
                        return $record->correct . '/' . $record->total_questions;
                    }),
            ])
            ->heading('Leaderboard')
            ->paginated(false);
    }
}
