<?php

namespace App\Models;

use App\Enums\FeedbackTiming;
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
        'questions_per_attempt',
        'show_correct_answers',
        'show_explanations',
        'feedback_timing',
        'passing_score',
        'require_passing_to_proceed',
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
            'questions_per_attempt' => 'integer',
            'show_correct_answers' => 'boolean',
            'show_explanations' => 'boolean',
            'feedback_timing' => FeedbackTiming::class,
            'passing_score' => 'integer',
            'require_passing_to_proceed' => 'boolean',
        ];
    }

    public function getQuestionsToShowCount(): int
    {
        if ($this->questions_per_attempt && $this->questions_per_attempt > 0) {
            return min($this->questions_per_attempt, $this->questions()->count());
        }

        return $this->questions()->count();
    }

    public function isPassing(int $correctCount, int $totalQuestions): bool
    {
        if (! $this->passing_score || $totalQuestions === 0) {
            return true;
        }

        $percentage = ($correctCount / $totalQuestions) * 100;

        return $percentage >= $this->passing_score;
    }

    public function shouldShowFeedback(?Test $test = null): bool
    {
        $timing = $this->feedback_timing ?? FeedbackTiming::AfterSubmit;

        return match ($timing) {
            FeedbackTiming::Never => false,
            FeedbackTiming::Immediate => true,
            FeedbackTiming::AfterSubmit => $test?->isSubmitted() ?? false,
            FeedbackTiming::AfterDeadline => $this->end_time && now()->isAfter($this->end_time),
        };
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
