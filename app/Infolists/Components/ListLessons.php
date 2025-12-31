<?php

namespace App\Infolists\Components;

use App\Filament\Student\Resources\CourseResource;
use App\Models\Course;
use Filament\Schemas\Components\Component;

class ListLessons extends Component
{
    protected string $view = 'infolists.components.list-lessons';

    protected ?Course $course = null;

    protected $lessons = null;

    protected $activeLesson = null;

    public static function make(string $name): static
    {
        return new static;
    }

    public function course($course): static
    {
        $this->course = $course;
        $this->lessons = $course->publishedLessons;

        $this->viewData([
            'course' => $this->course,
            'lessons' => $this->lessons,
        ]);

        return $this;
    }

    public function activeLesson($lesson): static
    {
        $this->activeLesson = $lesson;

        return $this;
    }

    public function getActiveLesson()
    {
        return $this->activeLesson;
    }

    public function isActive($lesson): bool
    {
        return $this->activeLesson?->id === $lesson->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function getLessons()
    {
        return $this->lessons;
    }

    public function getUrl($lesson): string
    {
        return CourseResource::getUrl('lessons.view', [
            'parent' => $this->course,
            'record' => $lesson,
        ]);
    }
}
