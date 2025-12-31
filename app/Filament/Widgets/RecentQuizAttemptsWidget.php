<?php

namespace App\Filament\Widgets;

use App\Models\Test;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentQuizAttemptsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Quiz Attempts';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Test::query()
                    ->with(['user', 'quiz'])
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('quiz.title')
                    ->label('Quiz')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('score_display')
                    ->label('Score')
                    ->getStateUsing(function (Test $record): string {
                        $total = $record->quiz?->questions()->count() ?? 0;

                        return $total > 0
                            ? round(($record->result / $total) * 100).'%'
                            : '-';
                    })
                    ->badge()
                    ->color(function (Test $record): string {
                        $total = $record->quiz?->questions()->count() ?? 1;
                        $percentage = ($record->result / $total) * 100;

                        return $percentage >= 70 ? 'success' : 'danger';
                    }),

                TextColumn::make('result')
                    ->label('Correct')
                    ->formatStateUsing(function (Test $record): string {
                        $total = $record->quiz?->questions()->count() ?? 0;

                        return "{$record->result}/{$total}";
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Test $record): string {
                        $total = $record->quiz?->questions()->count() ?? 1;
                        $percentage = ($record->result / $total) * 100;

                        return $percentage >= 70 ? 'Passed' : 'Failed';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Passed' ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}
