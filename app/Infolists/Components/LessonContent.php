<?php

namespace App\Infolists\Components;

use Filament\Schemas\Components\Component;
use App\Models\Lesson;

class LessonContent extends Component
{
    protected string $view = 'infolists.components.lesson-content';

    public static function make(): static
    {
        return app(static::class);
    }

    public function getLesson(): Lesson
    {
        /** @var Lesson $lesson */
        $lesson = $this->getRecord();

        return $lesson;
    }

    public function isVideoLesson(): bool
    {
        return $this->getLesson()->type === Lesson::TYPE_VIDEO && filled($this->getLesson()->video_url);
    }

    public function getEmbedUrl(): ?string
    {
        $url = $this->getLesson()->video_url;

        if (! $url) {
            return null;
        }

        $pattern = '~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~i';

        if (preg_match($pattern, $url, $matches)) {
            $id = $matches[1];

            return "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1";
        }

        return null;
    }
}

