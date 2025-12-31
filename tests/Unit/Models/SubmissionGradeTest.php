<?php

namespace Tests\Unit\Models;

use App\Models\AssignmentSubmission;
use App\Models\SubmissionGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionGradeTest extends TestCase
{
    use RefreshDatabase;

    public function test_grade_belongs_to_submission(): void
    {
        $submission = AssignmentSubmission::factory()->create();
        $grade = SubmissionGrade::factory()->create(['submission_id' => $submission->id]);

        $this->assertTrue($grade->submission->is($submission));
    }

    public function test_grade_belongs_to_grader(): void
    {
        $grader = User::factory()->admin()->create();
        $grade = SubmissionGrade::factory()->create(['graded_by' => $grader->id]);

        $this->assertTrue($grade->grader->is($grader));
    }

    public function test_approve_uses_ai_values_by_default(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $grade = SubmissionGrade::factory()->aiGraded(85)->create();

        $grade->approve();

        $grade->refresh();

        $this->assertEquals(85, $grade->final_score);
        $this->assertEquals('approved', $grade->approval_status);
        $this->assertNotNull($grade->approved_at);
    }

    public function test_approve_with_custom_values(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $grade = SubmissionGrade::factory()->aiGraded(85)->create();

        $grade->approve(90, 'Great work!');

        $grade->refresh();

        $this->assertEquals(90, $grade->final_score);
        $this->assertEquals('Great work!', $grade->final_feedback);
    }

    public function test_approve_updates_submission_status(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $submission = AssignmentSubmission::factory()->submitted()->create();
        $grade = SubmissionGrade::factory()->aiGraded(85)->create([
            'submission_id' => $submission->id,
        ]);

        $grade->approve();

        $submission->refresh();

        $this->assertEquals('approved', $submission->status);
    }

    public function test_reject_sets_status(): void
    {
        $grade = SubmissionGrade::factory()->aiGraded(85)->create();

        $grade->reject();

        $this->assertEquals('rejected', $grade->approval_status);
    }

    public function test_modify_sets_custom_score(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $grade = SubmissionGrade::factory()->aiGraded(85)->create();

        $grade->modify(75, 'Needs improvement');

        $grade->refresh();

        $this->assertEquals(75, $grade->final_score);
        $this->assertEquals('Needs improvement', $grade->final_feedback);
        $this->assertEquals('modified', $grade->approval_status);
    }

    public function test_is_pending_returns_correct_value(): void
    {
        $pendingGrade = SubmissionGrade::factory()->create(['approval_status' => 'pending']);
        $approvedGrade = SubmissionGrade::factory()->create(['approval_status' => 'approved']);

        $this->assertTrue($pendingGrade->isPending());
        $this->assertFalse($approvedGrade->isPending());
    }

    public function test_is_approved_returns_true_for_approved_and_modified(): void
    {
        $approved = SubmissionGrade::factory()->create(['approval_status' => 'approved']);
        $modified = SubmissionGrade::factory()->create(['approval_status' => 'modified']);
        $pending = SubmissionGrade::factory()->create(['approval_status' => 'pending']);

        $this->assertTrue($approved->isApproved());
        $this->assertTrue($modified->isApproved());
        $this->assertFalse($pending->isApproved());
    }
}
