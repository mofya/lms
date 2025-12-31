<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\SubmissionGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submission_belongs_to_assignment(): void
    {
        $assignment = Assignment::factory()->create();
        $submission = AssignmentSubmission::factory()->create(['assignment_id' => $assignment->id]);

        $this->assertTrue($submission->assignment->is($assignment));
    }

    public function test_submission_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $submission = AssignmentSubmission::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($submission->user->is($user));
    }

    public function test_submission_has_one_grade(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        $grade = SubmissionGrade::factory()->create(['submission_id' => $submission->id]);

        $this->assertTrue($submission->grade->is($grade));
    }

    public function test_mark_as_submitted_updates_status(): void
    {
        $assignment = Assignment::factory()->available()->create();
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => 'draft',
        ]);

        $submission->markAsSubmitted();

        $this->assertEquals('submitted', $submission->status);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_mark_as_submitted_sets_late_flag_when_past_due(): void
    {
        $assignment = Assignment::factory()->create([
            'is_published' => true,
            'due_at' => now()->subDay(),
        ]);

        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => 'draft',
        ]);

        $submission->markAsSubmitted();

        $this->assertTrue($submission->is_late);
    }

    public function test_check_if_late_returns_false_when_no_due_date(): void
    {
        $assignment = Assignment::factory()->create(['due_at' => null]);
        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
        ]);

        $this->assertFalse($submission->checkIfLate());
    }

    public function test_is_graded_returns_true_for_graded_status(): void
    {
        $submission = AssignmentSubmission::factory()->graded()->create();

        $this->assertTrue($submission->isGraded());
    }

    public function test_is_graded_returns_true_for_approved_status(): void
    {
        $submission = AssignmentSubmission::factory()->approved()->create();

        $this->assertTrue($submission->isGraded());
    }

    public function test_is_graded_returns_false_for_submitted_status(): void
    {
        $submission = AssignmentSubmission::factory()->submitted()->create();

        $this->assertFalse($submission->isGraded());
    }

    public function test_has_ai_grade_returns_true_when_ai_score_exists(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        SubmissionGrade::factory()->aiGraded(85)->create(['submission_id' => $submission->id]);

        $submission->refresh();

        $this->assertTrue($submission->hasAiGrade());
    }

    public function test_has_ai_grade_returns_false_when_no_grade(): void
    {
        $submission = AssignmentSubmission::factory()->create();

        $this->assertFalse($submission->hasAiGrade());
    }

    public function test_get_final_score_returns_final_score_when_set(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        SubmissionGrade::factory()->aiGraded(80)->approved(90)->create([
            'submission_id' => $submission->id,
        ]);

        $submission->refresh();

        $this->assertEquals(90, $submission->getFinalScore());
    }

    public function test_get_final_score_returns_ai_score_when_no_final(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        SubmissionGrade::factory()->aiGraded(85)->create(['submission_id' => $submission->id]);

        $submission->refresh();

        $this->assertEquals(85, $submission->getFinalScore());
    }

    public function test_get_final_score_returns_null_when_no_grade(): void
    {
        $submission = AssignmentSubmission::factory()->create();

        $this->assertNull($submission->getFinalScore());
    }
}
