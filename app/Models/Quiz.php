<?php

namespace App\Models;

use App\Enums\NavigatorPosition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_published',
        'start_time',
        'end_time',
        'duration_per_question',
        'total_duration',
        'course_id',
        'attempts_allowed',
        'show_one_question_at_a_time',
        'navigator_position',
        'shuffle_questions',
        'shuffle_options',
        'show_progress_bar',
        'allow_question_navigation',
        'auto_advance_on_answer',
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
            'show_one_question_at_a_time' => 'boolean',
            'navigator_position' => NavigatorPosition::class,
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_progress_bar' => 'boolean',
            'allow_question_navigation' => 'boolean',
            'auto_advance_on_answer' => 'boolean',
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
