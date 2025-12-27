<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'ai_score',
        'ai_feedback',
        'ai_criteria_scores',
        'ai_provider',
        'ai_graded_at',
        'final_score',
        'final_feedback',
        'final_criteria_scores',
        'graded_by',
        'approval_status',
        'approved_at',
    ];

    protected $casts = [
        'ai_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'ai_criteria_scores' => 'array',
        'final_criteria_scores' => 'array',
        'ai_graded_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class, 'submission_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function approve(?float $score = null, ?string $feedback = null, ?array $criteriaScores = null): void
    {
        $this->update([
            'final_score' => $score ?? $this->ai_score,
            'final_feedback' => $feedback ?? $this->ai_feedback,
            'final_criteria_scores' => $criteriaScores ?? $this->ai_criteria_scores,
            'approval_status' => 'approved',
            'approved_at' => now(),
            'graded_by' => auth()->id(),
        ]);

        $this->submission->update(['status' => 'approved']);
    }

    public function reject(): void
    {
        $this->update([
            'approval_status' => 'rejected',
        ]);
    }

    public function modify(float $score, ?string $feedback = null, ?array $criteriaScores = null): void
    {
        $this->update([
            'final_score' => $score,
            'final_feedback' => $feedback ?? $this->ai_feedback,
            'final_criteria_scores' => $criteriaScores ?? $this->ai_criteria_scores,
            'approval_status' => 'modified',
            'approved_at' => now(),
            'graded_by' => auth()->id(),
        ]);

        $this->submission->update(['status' => 'approved']);
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return in_array($this->approval_status, ['approved', 'modified']);
    }
}
