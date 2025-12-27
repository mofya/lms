<?php

namespace App\Infolists\Components;

use Filament\Schemas\Components\Component;
use Closure;

class CourseProgress extends Component
{
    protected string $view = 'infolists.components.course-progress';

    protected int $progress = 0;

    protected int $progressMax = 0;

    protected int $percentage = 0;

    public static function make(): static
    {
        return app(static::class);
    }

    public function course($course)
    {
        $progress = $course->progress();

        $this->progress    = $progress['value'];
        $this->progressMax = $progress['max'];
        $this->percentage  = $progress['percentage'];

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getProgressMax():int
    {
        return $this->progressMax;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }
}
