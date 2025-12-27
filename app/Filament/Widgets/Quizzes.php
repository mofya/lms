<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables;
use App\Models\Quiz;
use Filament\Tables\Table;
use App\Filament\Pages\TakeQuiz;
use App\Filament\Resources\QuizResource;
use Filament\Widgets\TableWidget as BaseWidget;

class Quizzes extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quiz::query()->published()->withCount('questions')
            )
            ->columns([
                TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function (Quiz $record): string {
                        if (!$record->shouldUseTotalDuration()) {
                            $totalTime = $record->duration_per_question * $record->questions_count;
                            return "{$totalTime} seconds";
                        }
                        return "{$record->total_duration} seconds";
                    })
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->label('Questions')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('takeQuiz')
                    ->label('Start Quiz')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Start Quiz')
                    ->modalDescription('Are you sure you want to start this quiz?')
                    ->modalSubmitActionLabel('Yes, start quiz')
                    ->action(fn (Quiz $record) => redirect()->to(TakeQuiz::getUrl(['record' => $record->id])))
            ]);
    }
}
