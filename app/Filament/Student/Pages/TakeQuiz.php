<?php

namespace App\Filament\Student\Pages;

use App\Enums\NavigatorPosition;
use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Models\TestAnswer;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany as Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class TakeQuiz extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.student.pages.take-quiz';

    #[Locked]
    public Model|int|string|null $record;

    public Quiz $quiz;

    public Collection $questions;

    public Question $currentQuestion;

    public int $currentQuestionIndex = 0;

    public array $questionsAnswers = [];

    public ?int $secondsPerQuestion = 60;

    public ?int $totalDuration = 900;

    public ?int $startTimeSeconds = null;

    public ?int $attemptNumber = null;

    public function mount(int|string $record): void
    {
        $this->record = Quiz::findOrFail($record);

        abort_if(! $this->record->is_published, 404, 'This quiz is not available.');

        if ($this->record->start_time && $this->record->end_time) {
            abort_if(! $this->record->isActive(), 403, 'This quiz is not currently available.');
        }

        abort_if(
            $this->record->course
                && ! $this->record->course->students()->where('users.id', auth()->id())->exists(),
            403,
            'You are not enrolled in this course.'
        );

        $attemptsSoFar = $this->record->tests()->where('user_id', auth()->id())->count();
        abort_if(
            $attemptsSoFar >= $this->record->attempts_allowed,
            403,
            'You have reached the maximum number of attempts for this quiz.'
        );
        $this->attemptNumber = $attemptsSoFar + 1;

        $this->quiz = $this->record;

        $questionsQuery = $this->record->questions();

        if ($this->record->shuffle_questions) {
            $questionsQuery->inRandomOrder();
        }

        if ($this->record->shuffle_options) {
            $questionsQuery->with(['questionOptions' => fn (Builder $query) => $query->inRandomOrder()]);
        } else {
            $questionsQuery->with('questionOptions');
        }

        $this->questions = $questionsQuery->get();

        if ($this->questions->isEmpty()) {
            abort(404, 'This quiz has no questions.');
        }

        $this->currentQuestion = $this->questions[0];

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

        if ($this->record->total_duration && $this->record->total_duration > 0) {
            $this->totalDuration = $this->record->total_duration;
            $this->secondsPerQuestion = null;
        } else {
            $this->totalDuration = null;
            $this->secondsPerQuestion = $this->record->duration_per_question ?? 60;
        }

        $this->startTimeSeconds = now()->timestamp;
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/'.static::getSlug().'/{record}';
    }

    public function goToQuestion(int $index): void
    {
        if (! $this->quiz->allow_question_navigation) {
            return;
        }

        if ($index >= 0 && $index < $this->questions->count()) {
            $this->currentQuestionIndex = $index;
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
        }
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
        if (! $this->quiz->allow_question_navigation) {
            return;
        }

        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
        }
    }

    public function updatedQuestionsAnswers($value, $key): void
    {
        if ($this->quiz->auto_advance_on_answer) {
            $question = $this->questions[$this->currentQuestionIndex];

            if ($question->type === 'multiple_choice' && $value !== null) {
                $this->dispatch('answer-saved');
                if ($this->currentQuestionIndex < count($this->questions) - 1) {
                    $this->currentQuestionIndex++;
                    $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
                }
            }
        }
    }

    public function submit(): void
    {
        $result = 0;
        $timeSpent = now()->timestamp - ($this->startTimeSeconds ?? now()->timestamp);

        $test = Test::create([
            'user_id' => auth()->id(),
            'quiz_id' => $this->record->id,
            'result' => 0,
            'ip_address' => request()->ip(),
            'time_spent' => $timeSpent,
            'attempt_number' => $this->attemptNumber ?? 1,
        ]);

        foreach ($this->questionsAnswers as $key => $rawAnswer) {
            $question = $this->questions[$key];

            $optionId = null;
            $userAnswer = null;

            if ($question->type === 'multiple_choice') {
                $optionId = $rawAnswer ?: null;
            } elseif ($question->type === 'checkbox') {
                $userAnswer = json_encode(array_values(array_filter((array) $rawAnswer)));
            } elseif ($question->type === 'single_answer') {
                $userAnswer = $rawAnswer;
            }

            $answer = TestAnswer::create([
                'user_id' => auth()->id(),
                'test_id' => $test->id,
                'question_id' => $question->id,
                'option_id' => $optionId,
                'user_answer' => $userAnswer,
                'correct' => false,
            ]);

            $isCorrect = $question->evaluateAnswer($answer);
            $answer->update(['correct' => $isCorrect]);

            if ($isCorrect) {
                $result++;
            }
        }

        $test->update(['result' => $result]);

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

    #[Computed]
    public function answeredCount(): int
    {
        $count = 0;
        foreach ($this->questionsAnswers as $answer) {
            if (is_array($answer) && count($answer) > 0) {
                $count++;
            } elseif (! is_array($answer) && $answer !== null && $answer !== '') {
                $count++;
            }
        }

        return $count;
    }

    #[Computed]
    public function progressPercentage(): float
    {
        if ($this->questionsCount === 0) {
            return 0;
        }

        return ($this->answeredCount / $this->questionsCount) * 100;
    }

    public function isQuestionAnswered(int $index): bool
    {
        $answer = $this->questionsAnswers[$index] ?? null;

        if (is_array($answer)) {
            return count($answer) > 0;
        }

        return $answer !== null && $answer !== '';
    }

    public function shouldUseTotalDuration(): bool
    {
        return $this->record->total_duration !== null && $this->record->total_duration > 0;
    }

    public function getNavigatorPosition(): string
    {
        return $this->quiz->navigator_position?->value ?? NavigatorPosition::Bottom->value;
    }

    protected function getViewData(): array
    {
        return [
            'quiz' => $this->record,
            'currentQuestion' => $this->currentQuestion ?? null,
            'totalDuration' => $this->shouldUseTotalDuration() ? $this->record->total_duration : null,
            'secondsPerQuestion' => ! $this->shouldUseTotalDuration() ? $this->record->duration_per_question : null,
            'navigatorPosition' => $this->getNavigatorPosition(),
        ];
    }
}
