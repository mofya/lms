<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'result',
        'ip_address',
        'time_spent',
        'user_id',
        'quiz_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'correct_count',
        'wrong_count',
        'question_ids',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'time_spent' => 'integer',
            'result' => 'integer',
            'question_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'test_answers', 'test_id', 'question_id');
    }

    public function test_answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }

    /**
     * Check if this test attempt is still in progress (not submitted).
     */
    public function isInProgress(): bool
    {
        return $this->submitted_at === null;
    }

    /**
     * Check if this test attempt has been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Calculate the score for this test.
     */
    public function calculateScore(): int
    {
        $score = 0;

        foreach ($this->testAnswers as $answer) {
            $question = $answer->question;
            if ($question && $question->evaluateAnswer($answer)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Get score as percentage.
     */
    public function getScorePercentageAttribute(): float
    {
        $totalQuestions = $this->testAnswers->count();
        if ($totalQuestions === 0) {
            return 0;
        }

        return round(($this->result / $totalQuestions) * 100, 2);
    }

    /**
     * Get the score attribute (calculates if not stored).
     */
    public function getScoreAttribute(): int
    {
        return $this->result ?? $this->calculateScore();
    }
}
