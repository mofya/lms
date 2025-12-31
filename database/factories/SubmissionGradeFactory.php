<?php

namespace Database\Factories;

use App\Models\AssignmentSubmission;
use App\Models\SubmissionGrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubmissionGradeFactory extends Factory
{
    protected $model = SubmissionGrade::class;

    public function definition(): array
    {
        return [
            'submission_id' => AssignmentSubmission::factory(),
            'ai_score' => null,
            'ai_feedback' => null,
            'ai_criteria_scores' => null,
            'ai_provider' => null,
            'ai_graded_at' => null,
            'final_score' => null,
            'final_feedback' => null,
            'final_criteria_scores' => null,
            'graded_by' => null,
            'approval_status' => 'pending',
            'approved_at' => null,
        ];
    }

    public function aiGraded(float $score = 85.0): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_score' => $score,
            'ai_feedback' => fake()->paragraphs(2, true),
            'ai_provider' => 'openai',
            'ai_graded_at' => now(),
        ]);
    }

    public function approved(float $score = null): static
    {
        return $this->state(function (array $attributes) use ($score) {
            $finalScore = $score ?? $attributes['ai_score'] ?? 85.0;
            return [
                'final_score' => $finalScore,
                'final_feedback' => $attributes['ai_feedback'] ?? fake()->paragraphs(2, true),
                'approval_status' => 'approved',
                'approved_at' => now(),
                'graded_by' => User::factory(),
            ];
        });
    }

    public function modified(float $score): static
    {
        return $this->state(fn (array $attributes) => [
            'final_score' => $score,
            'final_feedback' => fake()->paragraphs(2, true),
            'approval_status' => 'modified',
            'approved_at' => now(),
            'graded_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
        ]);
    }
}
