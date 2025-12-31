<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

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

    public function testAnswers(): HasMany
    {
        return $this->hasMany(TestAnswer::class);
    }
    /**
     * Calculate the score for this test.
     *
     * @return int
     */
    public function calculateScore(): int
    {
        $score = 0;

        foreach ($this->testAnswers as $answer) {
            // Get the question associated with this answer.
            $question = $answer->question;
            if ($question && $question->evaluateAnswer($answer)) {
                $score++;
            }
        }
        return $score;
    }

    public function getScoreAttribute(): int
    {
        return $this->calculateScore();
    }
}
