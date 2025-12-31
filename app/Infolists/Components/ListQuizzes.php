<?php

namespace App\Infolists\Components;

use App\Models\Quiz;
use App\Services\QuizTakingService;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Collection;

class ListQuizzes extends Component
{
    protected string $view = 'filament.infolists.components.list-quizzes';

    public ?Collection $quizzesWithStatus = null;

    public static function make(string $name): static
    {
        return new static;
    }

    public function course($course): static
    {
        $user = auth()->user();
        $quizService = app(QuizTakingService::class);

        $quizzes = Quiz::where('course_id', $course->id)
            ->published()
            ->withCount('questions')
            ->get();

        // Build quiz data with status for each quiz
        $this->quizzesWithStatus = $quizzes->map(function (Quiz $quiz) use ($user, $quizService) {
            $status = $user ? $quizService->getQuizStatusForUser($user, $quiz) : null;

            return [
                'quiz' => $quiz,
                'status' => $status,
            ];
        });

        return $this->configure();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configure();
    }

    public function configure(): static
    {
        $this->viewData([
            'quizzesWithStatus' => $this->quizzesWithStatus ?? collect(),
        ]);

        return $this;
    }
}
