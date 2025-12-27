<?php

namespace App\Filament\Student\Pages;

use Filament\Panel;
use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Test;
use App\Models\Quiz;
use Filament\Pages\Page;
use App\Models\Question;
use App\Models\TestAnswer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Relations\HasMany as Builder;

class TakeQuiz extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.student.pages.take-quiz';

    #[Locked]
    public Model | int | string | null $record;

    public Quiz $quiz;
    public Collection $questions;
    public Question $currentQuestion;
    public int $currentQuestionIndex = 0;
    public array $questionsAnswers = [];
    public ?int $secondsPerQuestion = 60;
    public ?int $totalDuration = 900;
    public ?int $startTimeSeconds = null;
    public ?int $attemptNumber = null;

    public function mount(int | string $record): void
    {
        $this->record = Quiz::findOrFail($record);

        // Ensure the quiz is published
        abort_if(!$this->record->is_published, 404, 'This quiz is not available.');

        // Enforce availability window if defined
        if ($this->record->start_time && $this->record->end_time) {
            abort_if(!$this->record->isActive(), 403, 'This quiz is not currently available.');
        }

        // Ensure the user is enrolled in the quiz's course (efficient query)
        abort_if(
            $this->record->course
                && !$this->record->course->students()->where('users.id', auth()->id())->exists(),
            403,
            'You are not enrolled in this course.'
        );

        // Enforce attempts limit
        $attemptsSoFar = $this->record->tests()->where('user_id', auth()->id())->count();
        abort_if(
            $attemptsSoFar >= $this->record->attempts_allowed,
            403,
            'You have reached the maximum number of attempts for this quiz.'
        );
        $this->attemptNumber = $attemptsSoFar + 1;

        $this->quiz = $this->record;

        $this->questions = $this->record->questions()
            ->inRandomOrder()
            ->with(['questionOptions' => fn (Builder $query) => $query->inRandomOrder()])
            ->get();

        if ($this->questions->isEmpty()) {
            abort(404, 'This quiz has no questions.');
        }

        $this->currentQuestion = $this->questions[0];

        // Initialize answers array based on question type
        $this->questionsAnswers = [];
        foreach ($this->questions as $index => $question) {
            if ($question->type === 'checkbox') {
                $this->questionsAnswers[$index] = [];
            } elseif ($question->type === 'single_answer') {
                $this->questionsAnswers[$index] = '';
            } else {
                $this->questionsAnswers[$index] = null;
            }
        }

        // Use database values for timing
        if ($this->record->total_duration && $this->record->total_duration > 0) {
            $this->totalDuration = $this->record->total_duration;
            $this->secondsPerQuestion = null;
        } else {
            $this->totalDuration = null;
            $this->secondsPerQuestion = $this->record->duration_per_question ?? 60;
        }

        // Set quiz start time
        $this->startTimeSeconds = now()->timestamp;
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/' . static::getSlug() . '/{record}';
    }

    public function changeQuestion(): void
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
        } else {
            $this->submit();
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
        }
    }

    public function submit(): void
    {
        $result = 0;
        $timeSpent = now()->timestamp - ($this->startTimeSeconds ?? now()->timestamp);

        $test = Test::create([
            'user_id'        => auth()->id(),
            'quiz_id'        => $this->record->id,
            'result'         => 0,
            'ip_address'     => request()->ip(),
            'time_spent'     => $timeSpent,
            'attempt_number' => $this->attemptNumber ?? 1,
        ]);

        foreach ($this->questionsAnswers as $key => $rawAnswer) {
            $question = $this->questions[$key];

            // Normalize answer payload based on question type
            $optionId = null;
            $userAnswer = null;

            if ($question->type === 'multiple_choice') {
                $optionId = $rawAnswer ?: null;
            } elseif ($question->type === 'checkbox') {
                // Expect an array of selected option IDs
                $userAnswer = json_encode(array_values(array_filter((array) $rawAnswer)));
            } elseif ($question->type === 'single_answer') {
                $userAnswer = $rawAnswer;
            }

            $answer = TestAnswer::create([
                'user_id'     => auth()->id(),
                'test_id'     => $test->id,
                'question_id' => $question->id,
                'option_id'   => $optionId,
                'user_answer' => $userAnswer,
                'correct'     => false, // will be updated after evaluation
            ]);

            // Evaluate the answer using the Question model's method
            $isCorrect = $question->evaluateAnswer($answer);
            $answer->update(['correct' => $isCorrect]);

            if ($isCorrect) {
                $result++;
            }
        }

        // Update Test Score
        $test->update(['result' => $result]);

        // Redirect to Results
        $this->redirectIntended(StudentResultResource::getUrl('view', ['record' => $test]));
    }

    public function getHeading(): string|Htmlable
    {
        return $this->record->title;
    }

    #[Computed]
    public function questionsCount(): int
    {
        return $this->questions->count();
    }

    public function shouldUseTotalDuration(): bool
    {
        return $this->record->total_duration !== null && $this->record->total_duration > 0;
    }

    protected function getViewData(): array
    {
        return [
            'quiz' => $this->record,
            'currentQuestion' => $this->currentQuestion ?? null,
            'totalDuration' => $this->shouldUseTotalDuration() ? $this->record->total_duration : null,
            'secondsPerQuestion' => !$this->shouldUseTotalDuration() ? $this->record->duration_per_question : null,
        ];
    }
}
