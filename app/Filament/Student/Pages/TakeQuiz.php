<?php

namespace App\Filament\Student\Pages;

use App\Enums\NavigatorPosition;
use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Test;
use App\Services\QuizTakingService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class TakeQuiz extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.student.pages.take-quiz';

    protected QuizTakingService $quizService;

    #[Locked]
    public Model|int|string|null $record;

    public Quiz $quiz;

    public ?Test $test = null;

    public Collection $questions;

    public Question $currentQuestion;

    public int $currentQuestionIndex = 0;

    public array $questionsAnswers = [];

    public ?int $secondsPerQuestion = 60;

    public ?int $totalDuration = 900;

    public ?int $startTimeSeconds = null;

    public ?int $remainingSeconds = null;

    public bool $isSaving = false;

    public function boot(QuizTakingService $quizService): void
    {
        $this->quizService = $quizService;
    }

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

        // Check if user can attempt this quiz
        if (! $this->quizService->canUserAttemptQuiz(auth()->user(), $this->record)) {
            abort(403, 'You have reached the maximum number of attempts for this quiz.');
        }

        $this->quiz = $this->record;

        // Get or create an attempt (supports resume)
        $this->test = $this->quizService->getOrCreateAttempt(auth()->user(), $this->record);

        // Prepare questions
        $this->questions = $this->quizService->prepareQuizQuestions($this->record);

        if ($this->questions->isEmpty()) {
            abort(404, 'This quiz has no questions.');
        }

        // Load question options with optional shuffling
        $this->questions = $this->questions->map(function ($question) {
            if ($this->record->shuffle_options) {
                $question->setRelation(
                    'questionOptions',
                    $question->questionOptions->shuffle()
                );
            }

            return $question;
        });

        $this->currentQuestion = $this->questions[0];

        // Initialize answers array with existing answers (for resume)
        $this->initializeAnswers();

        // Set up timing
        $this->setupTiming();
    }

    protected function initializeAnswers(): void
    {
        // Load existing answers from database
        $existingAnswers = $this->test->testAnswers()
            ->get()
            ->keyBy('question_id');

        $this->questionsAnswers = [];

        foreach ($this->questions as $index => $question) {
            $existingAnswer = $existingAnswers->get($question->id);

            if ($existingAnswer) {
                if ($question->type === 'checkbox') {
                    $this->questionsAnswers[$index] = json_decode($existingAnswer->user_answer ?? '[]', true) ?? [];
                } elseif ($question->type === 'single_answer') {
                    $this->questionsAnswers[$index] = $existingAnswer->user_answer ?? '';
                } else {
                    $this->questionsAnswers[$index] = $existingAnswer->option_id;
                }
            } else {
                if ($question->type === 'checkbox') {
                    $this->questionsAnswers[$index] = [];
                } elseif ($question->type === 'single_answer') {
                    $this->questionsAnswers[$index] = '';
                } else {
                    $this->questionsAnswers[$index] = null;
                }
            }
        }
    }

    protected function setupTiming(): void
    {
        if ($this->record->shouldUseTotalDuration()) {
            $this->totalDuration = $this->record->total_duration * 60; // Convert to seconds
            $this->secondsPerQuestion = null;

            // Calculate remaining time based on when quiz started
            $remainingFromService = $this->quizService->getRemainingTimeSeconds($this->test);
            $this->remainingSeconds = $remainingFromService ?? $this->totalDuration;

            if ($this->remainingSeconds <= 0) {
                $this->autoSubmit();

                return;
            }
        } else {
            $this->totalDuration = null;
            $this->secondsPerQuestion = $this->record->duration_per_question ?? 60;
            $this->remainingSeconds = null;
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
        // Save answer to database in real-time
        $this->saveCurrentAnswer($key);

        // Auto-advance if enabled for multiple choice
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

    protected function saveCurrentAnswer(int $questionIndex): void
    {
        $this->isSaving = true;

        try {
            $question = $this->questions[$questionIndex];
            $rawAnswer = $this->questionsAnswers[$questionIndex] ?? null;

            $optionId = null;
            $userAnswer = null;

            if ($question->type === 'multiple_choice') {
                $optionId = $rawAnswer ?: null;
            } elseif ($question->type === 'checkbox') {
                $userAnswer = json_encode(array_values(array_filter((array) $rawAnswer)));
            } elseif ($question->type === 'single_answer') {
                $userAnswer = $rawAnswer;
            }

            if ($optionId || $userAnswer) {
                $this->quizService->saveAnswer($this->test, $question->id, $optionId, $userAnswer);
            }

            $this->dispatch('answer-saved');
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to save answer')
                ->body('Please try again.')
                ->danger()
                ->send();
        } finally {
            $this->isSaving = false;
        }
    }

    #[On('timer-expired')]
    public function autoSubmit(): void
    {
        Notification::make()
            ->title('Time expired!')
            ->body('Your quiz has been automatically submitted.')
            ->warning()
            ->send();

        $this->submit();
    }

    public function submit(): void
    {
        try {
            // Submit the quiz using the service
            $this->test = $this->quizService->submitQuiz($this->test);

            Notification::make()
                ->title('Quiz submitted successfully!')
                ->success()
                ->send();

            $this->redirectIntended(StudentResultResource::getUrl('view', ['record' => $this->test]));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Submission failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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

    #[Computed]
    public function remainingAttemptsCount(): ?int
    {
        return $this->quizService->getRemainingAttempts(auth()->user(), $this->quiz);
    }

    #[Computed]
    public function attemptNumber(): int
    {
        return $this->test?->attempt_number ?? 1;
    }

    protected function getViewData(): array
    {
        return [
            'quiz' => $this->record,
            'test' => $this->test,
            'currentQuestion' => $this->currentQuestion ?? null,
            'totalDuration' => $this->shouldUseTotalDuration() ? $this->remainingSeconds : null,
            'secondsPerQuestion' => ! $this->shouldUseTotalDuration() ? $this->record->duration_per_question : null,
            'navigatorPosition' => $this->getNavigatorPosition(),
            'isSaving' => $this->isSaving,
        ];
    }
}
