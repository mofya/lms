<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'course_id',
        'title',
        'lesson_text',
        'is_published',
        'position',
        'type',
        'video_url',
        'duration_seconds',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    protected $attributes = [
        'type' => self::TYPE_TEXT,
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function getNext(): ?self
    {
        $lessons = $this->course->publishedLessons()->get();

        $currentIndex = $lessons->search(fn (Lesson $lesson) => $lesson->is($this));

        if ($currentIndex === $lessons->keys()->last()) {
            return null;
        }

        return $lessons[$currentIndex + 1];
    }
    public function getPrevious(): ?self
    {
        $lessons = $this->course->publishedLessons()->get();

        $currentIndex = $lessons->search(fn (Lesson $lesson) => $lesson->is($this));

        if ($currentIndex === 0) {
            return null;
        }

        return $lessons[$currentIndex - 1];
    }
    public function isCompleted(): bool
    {
        return auth()->user()->completedLessons->containsStrict('id', $this->id);
    }
    public function markAsCompleted(): self
    {
        if ($this->isCompleted()) {
            return $this;
        }

        auth()->user()->completeLesson($this);
        auth()->user()->refresh();

        return $this;
    }
    public function markAsUncompleted(): self
    {
        if (! $this->isCompleted()) {
            return $this;
        }

        auth()->user()->uncompleteLesson($this);
        auth()->user()->refresh();

        return $this;
    }

}
