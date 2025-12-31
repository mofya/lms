<?php

namespace Tests\Unit\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Services\LlmAssignmentGrader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmAssignmentGraderTest extends TestCase
{
    use RefreshDatabase;

    private LlmAssignmentGrader $grader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grader = new LlmAssignmentGrader;
    }

    public function test_providers_constant_contains_expected_values(): void
    {
        $this->assertContains('openai', LlmAssignmentGrader::PROVIDERS);
        $this->assertContains('anthropic', LlmAssignmentGrader::PROVIDERS);
        $this->assertContains('gemini', LlmAssignmentGrader::PROVIDERS);
    }

    public function test_grade_submission_returns_false_when_api_key_missing(): void
    {
        config(['services.openai.api_key' => null]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'openai');

        $this->assertFalse($result);
        $this->assertEquals('submitted', $submission->fresh()->status);
    }

    public function test_grade_submission_updates_status_to_grading_during_process(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 85,
                                'feedback' => 'Great work!',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $this->grader->gradeSubmission($submission, 'openai');

        $this->assertEquals('graded', $submission->fresh()->status);
    }

    public function test_grade_submission_creates_grade_record(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 90,
                                'feedback' => 'Excellent work!',
                                'plagiarism_concerns' => 'none',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'openai');

        $this->assertTrue($result);

        $grade = $submission->fresh()->grade;
        $this->assertNotNull($grade);
        $this->assertEquals(90, $grade->ai_score);
        $this->assertEquals('Excellent work!', $grade->ai_feedback);
        $this->assertEquals('openai', $grade->ai_provider);
        $this->assertEquals('pending', $grade->approval_status);
    }

    public function test_grade_submission_handles_criteria_scores(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 85,
                                'feedback' => 'Good work!',
                                'criteria_scores' => [
                                    'Content' => 40,
                                    'Grammar' => 25,
                                    'Structure' => 20,
                                ],
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $this->grader->gradeSubmission($submission, 'openai');

        $grade = $submission->fresh()->grade;
        $this->assertIsArray($grade->ai_criteria_scores);
        $this->assertEquals(40, $grade->ai_criteria_scores['Content']);
    }

    public function test_grade_submission_returns_false_on_api_failure(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => ['message' => 'Server error'],
            ], 500),
        ]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'openai');

        $this->assertFalse($result);
        $this->assertEquals('submitted', $submission->fresh()->status);
    }

    public function test_grade_submission_returns_false_on_unsupported_provider(): void
    {
        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'unknown');

        $this->assertFalse($result);
    }

    public function test_batch_grade_processes_multiple_submissions(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 80,
                                'feedback' => 'Good!',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $submissions = [
            $this->createSubmission(),
            $this->createSubmission(),
            $this->createSubmission(),
        ];

        $this->grader->batchGrade($submissions, 'openai');

        foreach ($submissions as $submission) {
            $this->assertEquals('graded', $submission->fresh()->status);
            $this->assertNotNull($submission->fresh()->grade);
        }
    }

    public function test_grade_submission_with_anthropic(): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    [
                        'text' => json_encode([
                            'score' => 75,
                            'feedback' => 'Anthropic feedback',
                        ]),
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'anthropic');

        $this->assertTrue($result);
        $this->assertEquals('anthropic', $submission->fresh()->grade->ai_provider);
    }

    public function test_grade_submission_with_gemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'score' => 88,
                                        'feedback' => 'Gemini feedback',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'gemini');

        $this->assertTrue($result);
        $this->assertEquals('gemini', $submission->fresh()->grade->ai_provider);
    }

    public function test_grade_submission_extracts_json_from_markdown(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n{\"score\": 95, \"feedback\": \"Perfect!\"}\n```",
                        ],
                    ],
                ],
            ]),
        ]);

        $submission = $this->createSubmission();

        $result = $this->grader->gradeSubmission($submission, 'openai');

        $this->assertTrue($result);
        $this->assertEquals(95, $submission->fresh()->grade->ai_score);
    }

    private function createSubmission(): AssignmentSubmission
    {
        $assignment = Assignment::factory()->create([
            'type' => 'text',
            'max_points' => 100,
        ]);

        return AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'content' => 'This is my submission content.',
            'status' => 'submitted',
        ]);
    }
}
