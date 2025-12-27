<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'instructions',
        'type',
        'allowed_file_types',
        'max_file_size_mb',
        'max_submissions',
        'max_points',
        'available_from',
        'due_at',
        'late_due_at',
        'late_penalty_percent',
        'is_published',
    ];

    protected $casts = [
        'allowed_file_types' => 'array',
        'is_published' => 'boolean',
        'available_from' => 'datetime',
        'due_at' => 'datetime',
        'late_due_at' => 'datetime',
        'max_submissions' => 'integer',
        'max_points' => 'integer',
        'max_file_size_mb' => 'integer',
        'late_penalty_percent' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function rubric(): HasOne
    {
        return $this->hasOne(Rubric::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_published) {
            return false;
        }

        if ($this->available_from && now()->lt($this->available_from)) {
            return false;
        }

        return true;
    }

    public function isOverdue(): bool
    {
        if (!$this->due_at) {
            return false;
        }

        $deadline = $this->late_due_at ?? $this->due_at;
        return now()->gt($deadline);
    }

    public function checkIfLate(): bool
    {
        if (!$this->due_at) {
            return false;
        }

        return now()->gt($this->due_at);
    }

    public function canSubmit(User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if ($this->isOverdue()) {
            return false;
        }

        if ($this->max_submissions > 0) {
            $submissionCount = $this->submissions()
                ->where('user_id', $user->id)
                ->where('status', '!=', 'draft')
                ->count();

            if ($submissionCount >= $this->max_submissions) {
                return false;
            }
        }

        return true;
    }
}
