<?php

namespace Tests\Unit\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Rubric;
use App\Models\RubricCriteria;
use App\Services\AssignmentGradingPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentGradingPromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentGradingPromptBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new AssignmentGradingPromptBuilder;
    }

    public function test_build_includes_educator_role(): void
    {
        $submission = $this->createSubmission();

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('expert educator', $prompt);
    }

    public function test_build_includes_assignment_title(): void
    {
        $submission = $this->createSubmission(['assignment_title' => 'Essay on Climate Change']);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('Essay on Climate Change', $prompt);
    }

    public function test_build_includes_max_points(): void
    {
        $submission = $this->createSubmission(['max_points' => 100]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('Maximum Points: 100', $prompt);
    }

    public function test_build_includes_assignment_instructions(): void
    {
        $submission = $this->createSubmission([
            'instructions' => 'Write a 500 word essay',
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('Write a 500 word essay', $prompt);
    }

    public function test_build_includes_submission_content(): void
    {
        $submission = $this->createSubmission([
            'content' => 'This is my essay submission about climate change.',
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('This is my essay submission', $prompt);
    }

    public function test_build_includes_structured_rubric_criteria(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->structured()->create(['assignment_id' => $assignment->id]);
        RubricCriteria::factory()->create([
            'rubric_id' => $rubric->id,
            'name' => 'Content Quality',
            'max_points' => 40,
        ]);
        RubricCriteria::factory()->create([
            'rubric_id' => $rubric->id,
            'name' => 'Grammar',
            'max_points' => 30,
        ]);

        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => 'Test content',
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('Content Quality', $prompt);
        $this->assertStringContainsString('40 points', $prompt);
        $this->assertStringContainsString('Grammar', $prompt);
        $this->assertStringContainsString('30 points', $prompt);
    }

    public function test_build_includes_freeform_rubric_text(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->freeform()->create([
            'assignment_id' => $assignment->id,
            'freeform_text' => 'Grade based on creativity and originality',
        ]);

        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => 'Test content',
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('creativity and originality', $prompt);
    }

    public function test_build_requests_json_response(): void
    {
        $submission = $this->createSubmission();

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('JSON format', $prompt);
        $this->assertStringContainsString('"score"', $prompt);
        $this->assertStringContainsString('"feedback"', $prompt);
    }

    public function test_build_requests_plagiarism_check(): void
    {
        $submission = $this->createSubmission();

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('plagiarism', $prompt);
    }

    public function test_build_includes_criteria_scores_for_structured_rubric(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->structured()->create(['assignment_id' => $assignment->id]);
        RubricCriteria::factory()->create([
            'rubric_id' => $rubric->id,
            'name' => 'Research',
            'max_points' => 25,
        ]);

        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => 'Test content',
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('criteria_scores', $prompt);
        $this->assertStringContainsString('"Research"', $prompt);
    }

    public function test_build_handles_no_content_submission(): void
    {
        $assignment = Assignment::factory()->create(['type' => 'text']);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => null,
        ]);

        $prompt = $this->builder->build($submission);

        $this->assertStringContainsString('No content submitted', $prompt);
    }

    private function createSubmission(array $overrides = []): AssignmentSubmission
    {
        $assignment = Assignment::factory()->create([
            'title' => $overrides['assignment_title'] ?? 'Test Assignment',
            'max_points' => $overrides['max_points'] ?? 100,
            'instructions' => $overrides['instructions'] ?? null,
            'type' => 'text',
        ]);

        return AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => $overrides['content'] ?? 'Sample submission content',
        ]);
    }
}
