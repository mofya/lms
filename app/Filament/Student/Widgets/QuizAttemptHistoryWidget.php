<?php

namespace App\Filament\Student\Widgets;

use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Test;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class QuizAttemptHistoryWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Quiz Attempts';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Test::query()
                    ->where('user_id', Auth::id())
                    ->with(['quiz'])
                    ->latest('created_at')
            )
            ->columns([
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

                TextColumn::make('time_spent')
                    ->label('Time')
                    ->formatStateUsing(function (?int $state): string {
                        if (! $state) {
                            return '-';
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;

                        return sprintf('%d:%02d', $minutes, $seconds);
                    }),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Test $record): string {
                        $total = $record->quiz?->questions()->count() ?? 1;
                        $percentage = ($record->result / $total) * 100;

                        return $percentage >= 70 ? 'Passed' : 'Failed';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Passed' ? 'success' : 'danger'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Test $record) => StudentResultResource\Pages\ViewStudentResult::getUrl(['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}
