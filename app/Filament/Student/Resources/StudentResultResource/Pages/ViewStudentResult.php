<?php

namespace App\Filament\Student\Resources\StudentResultResource\Pages;

use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Test;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStudentResult extends ViewRecord
{
    protected static string $resource = StudentResultResource::class;

    protected string $view = 'filament.student.pages.view-quiz-result';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'quiz',
            'test_answers.question.questionOptions',
            'test_answers.option',
        ]);

        $this->authorizeAccess();
    }

    public function getHeading(): string|Htmlable
    {
        return 'Quiz Results';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->quiz->title;
    }

    public function getTotalQuestions(): int
    {
        return $this->record->test_answers->count();
    }

    public function getCorrectCount(): int
    {
        return $this->record->test_answers->where('correct', true)->count();
    }

    public function getWrongCount(): int
    {
        return $this->record->test_answers->where('correct', false)->count();
    }

    public function getScorePercentage(): float
    {
        $total = $this->getTotalQuestions();

        if ($total === 0) {
            return 0;
        }

        return round(($this->getCorrectCount() / $total) * 100, 1);
    }

    public function isPassed(): bool
    {
        return $this->getScorePercentage() >= 70;
    }

    public function getFormattedTime(): string
    {
        $seconds = $this->record->time_spent ?? 0;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function canRetakeQuiz(): bool
    {
        $quiz = $this->record->quiz;
        $attemptCount = Test::where('user_id', auth()->id())
            ->where('quiz_id', $quiz->id)
            ->count();

        return $attemptCount < $quiz->attempts_allowed;
    }

    public function retakeQuiz(): void
    {
        $this->redirect(route('filament.student.pages.take-quiz', ['record' => $this->record->quiz_id]));
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
