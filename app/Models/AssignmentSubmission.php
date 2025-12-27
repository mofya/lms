<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'attempt_number',
        'content',
        'file_path',
        'file_name',
        'status',
        'submitted_at',
        'is_late',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(SubmissionGrade::class, 'submission_id');
    }

    public function markAsSubmitted(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'is_late' => $this->checkIfLate(),
        ]);
    }

    public function checkIfLate(): bool
    {
        if (!$this->assignment->due_at) {
            return false;
        }

        $deadline = $this->assignment->late_due_at ?? $this->assignment->due_at;
        return now()->gt($deadline);
    }

    public function isGraded(): bool
    {
        return in_array($this->status, ['graded', 'approved']);
    }

    public function hasAiGrade(): bool
    {
        return $this->grade && $this->grade->ai_score !== null;
    }

    public function getFinalScore(): ?float
    {
        if (!$this->grade) {
            return null;
        }

        return $this->grade->final_score ?? $this->grade->ai_score;
    }
}
