<?php

namespace App\Infolists\Components;

use Filament\Schemas\Components\Component;
use App\Filament\Student\Resources\CourseResource;
use Filament\Infolists\Components\Concerns\HasName;

class ListLessons extends Component
{
    use HasName;

    protected string $view = 'infolists.components.list-lessons';

    protected $course;

    protected $lessons;

    protected $activeLesson = null;

    final public function __construct(string $name)
    {
        $this->name($name);
        $this->statePath($name);
    }

    public function course($course)
    {
        $this->course  = $course;
        $this->lessons = $course->publishedLessons;

        return $this;
    }

    public function activeLesson($lesson)
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

    public function getCourse()
    {
        return $this->course;
    }

    public function getLessons()
    {
        return $this->lessons;
    }

    public function getUrl($lesson)
    {
        return CourseResource::getUrl('lessons.view', [
            'parent' => $this->course,
            'record' => $lesson,
        ]);
    }

    public static function make(string $name): static
    {
        return app(static::class, ['name' => $name]);
    }
}
