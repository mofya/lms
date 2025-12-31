<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_SINGLE_ANSWER = 'single_answer';

    protected $fillable = [
        'question_text',
        'code_snippet',
        'answer_explanation',
        'more_info_link',
        'type',
        'correct_answer',

    ];

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class);
    }
    public function correctOptions()
    {
        return $this->hasMany(QuestionOption::class)->where('correct', true);
    }
    /**
     * Evaluate a given TestAnswer instance.
     *
     * @param TestAnswer $answer
     * @return bool
     */
    public function evaluateAnswer(TestAnswer $answer): bool
    {
        if (in_array($this->type, [self::TYPE_MULTIPLE_CHOICE, self::TYPE_CHECKBOX], true)) {
            $correctOptionIds = $this->correctOptions()->pluck('id')->toArray();

            if ($this->type === self::TYPE_MULTIPLE_CHOICE) {
                return in_array($answer->option_id, $correctOptionIds, true);
            }

            // Checkbox: compare full set of selected options vs. correct options
            $userOptions = collect(json_decode($answer->user_answer ?? '[]', true) ?? [])->map(fn ($id) => (int) $id)->sort()->values()->all();
            sort($correctOptionIds);

            return $correctOptionIds === $userOptions;
        }

        if ($this->type === self::TYPE_SINGLE_ANSWER) {
            $userResponse = strtolower(trim((string) $answer->user_answer));
            $correctAnswer = strtolower(trim((string) $this->correct_answer));

            return $correctAnswer !== '' && $userResponse === $correctAnswer;
        }

        return false;
    }

}
