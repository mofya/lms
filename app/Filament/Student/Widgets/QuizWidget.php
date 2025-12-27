<?php

namespace App\Filament\Student\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\Action;
use App\Filament\Pages\TakeQuiz;
use App\Models\Quiz;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizWidget extends BaseWidget
{
    protected static ?string $heading = 'My Quizzes';

    protected static bool $isFullWidth = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull(); // Ensures full width
    }
    protected function getTableQuery():Builder
    {
        $user = Auth::user();

        return Quiz::query()
            ->whereIn('course_id', $user->courses()->pluck('courses.id'))
            ->where('is_published', true)
            ->whereNotExists(function ($query) use ($user) {
                $query->select(DB::raw(1))
                    ->from('tests')
                    ->whereColumn('tests.quiz_id', 'quizzes.id')
                    ->where('user_id', $user->id)
                    ->groupBy('tests.quiz_id')
                    ->havingRaw('COUNT(*) >= quizzes.attempts_allowed');
            });
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('title')
                ->sortable()
                ->searchable(),
            TextColumn::make('course.title')
                ->label('Course')
                ->sortable(),
            TextColumn::make('attempts_allowed'),
            TextColumn::make('questions_count')
                ->counts('questions'),
            IconColumn::make('is_published')
                ->boolean(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('takeQuiz')
                ->label('Start Quiz')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Start Quiz')
                ->modalDescription('Are you sure you want to start this quiz?')
                ->modalSubmitActionLabel('Yes, start quiz')
                ->action(fn (Quiz $record) => redirect()->to(route('filament.student.pages.take-quiz', ['record' => $record->id]))),
        ];
    }
}