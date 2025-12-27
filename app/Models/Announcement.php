<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'content',
        'is_pinned',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('published_at')
              ->orWhere('published_at', '<=', now());
        })->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeForCourse(Builder $query, ?int $courseId): Builder
    {
        if ($courseId === null) {
            return $query->whereNull('course_id');
        }
        
        return $query->where(function ($q) use ($courseId) {
            $q->whereNull('course_id')
              ->orWhere('course_id', $courseId);
        });
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function isPublished(): bool
    {
        return ($this->published_at === null || $this->published_at <= now()) &&
               ($this->expires_at === null || $this->expires_at >= now());
    }

    public function isGlobal(): bool
    {
        return $this->course_id === null;
    }
}
