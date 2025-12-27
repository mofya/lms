<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'is_published',
        'start_time',
        'end_time',
        'duration_per_question',
        'total_duration',
        'course_id',
        'attempts_allowed'
    ];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_per_question' => 'integer',
            'total_duration' => 'integer',
        ];
    }
    public function isActive(): bool
    {
        $now = now();
        return ($this->start_time && $this->end_time) ? ($now >= $this->start_time && $now <= $this->end_time) : false;
    }
    public function shouldUseTotalDuration(): bool
    {
        return $this->total_duration !== null && $this->total_duration > 0;
    }
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class, 'quiz_id');
    }
}
