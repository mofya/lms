<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Course extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'lecturer_id',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function getDescriptionAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        // If it's already a JSON array, return as-is
        if (is_array($value)) {
            return $value;
        }

        // Try to decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // If it's HTML/plain text, convert to Tiptap JSON format
        $text = trim(strip_tags($value));
        if ($text === '') {
            return null;
        }

        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function setDescriptionAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['description'] = json_encode($value);
        } else {
            $this->attributes['description'] = $value;
        }
    }

    public function getDescriptionTextAttribute(): ?string
    {
        $description = $this->description;

        if (is_null($description)) {
            return null;
        }

        if (is_string($description)) {
            return $description;
        }

        if (is_array($description)) {
            return $this->extractTextFromTiptap($description);
        }

        return null;
    }

    private function extractTextFromTiptap(array $content): string
    {
        $text = '';

        if (isset($content['text'])) {
            return $content['text'];
        }

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $node) {
                $text .= $this->extractTextFromTiptap($node);
            }
        }

        return $text;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user', 'course_id', 'user_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function publishedLessons(): HasMany
    {
        return $this->lessons()->published();
    }

    public function progress(): array
    {
        $lessons = $this->publishedLessons;
        $completed = auth()->user()->completedLessons()
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->count();

        return [
            'value' => $completed,
            'max' => $lessons->count(),
            'percentage' => (int) floor(($completed / max(1, $lessons->count())) * 100),
        ];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
