<?php

namespace App\Infolists\Components;

use Filament\Schemas\Components\Component;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Collection;

class ListQuizzes extends Component
{
    protected string $view = 'filament.infolists.components.list-quizzes';

    public ?Collection $quizzes = null;

    public static function make(string $name): static
    {
        return new static();
    }

    public function course($course): static
    {
        $this->quizzes = Quiz::where('course_id', $course->id)->get();

        return $this->configure();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the data is correctly passed to the view
        $this->configure();
    }

    public function configure(): static
    {
//        dd($this->quizzes);
        $this->viewData([
            'quizzes' => $this->quizzes ?? collect(), // Ensure it's always a collection
        ]);

        return $this;
    }
}