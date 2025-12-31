<?php

namespace Tests\Unit\Services;

use App\Models\Course;
use App\Services\PromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private PromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new PromptBuilder;
    }

    public function test_build_includes_educator_role(): void
    {
        $prompt = $this->builder->build([]);

        $this->assertStringContainsString('expert educator', $prompt);
    }

    public function test_build_includes_topic_when_provided(): void
    {
        $prompt = $this->builder->build(['topic' => 'Laravel Routing']);

        $this->assertStringContainsString('Laravel Routing', $prompt);
    }

    public function test_build_includes_num_questions(): void
    {
        $prompt = $this->builder->build(['num_questions' => 10]);

        $this->assertStringContainsString('Total questions: 10', $prompt);
    }

    public function test_build_limits_num_questions_to_50(): void
    {
        $prompt = $this->builder->build(['num_questions' => 100]);

        $this->assertStringContainsString('Total questions: 50', $prompt);
    }

    public function test_build_ensures_minimum_1_question(): void
    {
        $prompt = $this->builder->build(['num_questions' => 0]);

        $this->assertStringContainsString('Total questions: 1', $prompt);
    }

    public function test_build_normalizes_easy_difficulty(): void
    {
        $prompt = $this->builder->build(['difficulty' => 'beginner']);

        $this->assertStringContainsString('Difficulty: easy', $prompt);
        $this->assertStringContainsString('beginners', $prompt);
    }

    public function test_build_normalizes_medium_difficulty(): void
    {
        $prompt = $this->builder->build(['difficulty' => 'intermediate']);

        $this->assertStringContainsString('Difficulty: medium', $prompt);
    }

    public function test_build_normalizes_hard_difficulty(): void
    {
        $prompt = $this->builder->build(['difficulty' => 'advanced']);

        $this->assertStringContainsString('Difficulty: hard', $prompt);
    }

    public function test_build_defaults_to_mixed_difficulty(): void
    {
        $prompt = $this->builder->build(['difficulty' => 'unknown']);

        $this->assertStringContainsString('Difficulty: mixed', $prompt);
    }

    public function test_build_includes_question_types(): void
    {
        $prompt = $this->builder->build([
            'question_types' => [
                ['type' => 'multiple_choice', 'count' => 5],
                ['type' => 'checkbox', 'count' => 3],
            ],
        ]);

        $this->assertStringContainsString('5 multiple choice', $prompt);
        $this->assertStringContainsString('3 checkbox', $prompt);
    }

    public function test_build_includes_code_snippets_note_when_enabled(): void
    {
        $prompt = $this->builder->build(['include_code_snippets' => true]);

        $this->assertStringContainsString('include concise code snippets', $prompt);
    }

    public function test_build_excludes_code_snippets_note_when_disabled(): void
    {
        $prompt = $this->builder->build(['include_code_snippets' => false]);

        $this->assertStringContainsString('Do not include code snippets', $prompt);
    }

    public function test_build_includes_additional_instructions(): void
    {
        $prompt = $this->builder->build([
            'additional_instructions' => 'Focus on security concepts',
        ]);

        $this->assertStringContainsString('Focus on security concepts', $prompt);
    }

    public function test_build_includes_json_schema(): void
    {
        $prompt = $this->builder->build([]);

        $this->assertStringContainsString('question_text', $prompt);
        $this->assertStringContainsString('answer_explanation', $prompt);
        $this->assertStringContainsString('options', $prompt);
    }

    public function test_build_includes_course_context_when_provided(): void
    {
        $course = Course::factory()->create([
            'title' => 'Advanced PHP Programming',
            'description' => 'Learn advanced PHP concepts',
        ]);

        $prompt = $this->builder->build(['course_id' => $course->id]);

        $this->assertStringContainsString('Advanced PHP Programming', $prompt);
    }

    public function test_build_handles_missing_course(): void
    {
        $prompt = $this->builder->build(['course_id' => 99999]);

        $this->assertStringContainsString('Course context not provided', $prompt);
    }

    public function test_build_handles_null_course_id(): void
    {
        $prompt = $this->builder->build(['course_id' => null]);

        $this->assertStringContainsString('Course context not provided', $prompt);
    }

    public function test_build_instructs_json_only_output(): void
    {
        $prompt = $this->builder->build([]);

        $this->assertStringContainsString('Respond with JSON only', $prompt);
        $this->assertStringContainsString('no commentary', $prompt);
    }
}
