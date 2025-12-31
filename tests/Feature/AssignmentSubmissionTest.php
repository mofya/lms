<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Badge;
use App\Models\Course;
use App\Models\Rubric;
use App\Models\RubricCriteria;
use App\Models\SubmissionGrade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_submission(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->available()->create();

        $submission = AssignmentSubmission::factory()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
        ]);

        $this->assertDatabaseHas('assignment_submissions', [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
        ]);
    }

    public function test_submission_respects_max_submissions(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->available()->create(['max_submissions' => 2]);

        // Create 2 submitted submissions
        AssignmentSubmission::factory()->submitted()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'attempt_number' => 1,
        ]);

        AssignmentSubmission::factory()->submitted()->create([
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'attempt_number' => 2,
        ]);

        $this->assertFalse($assignment->canSubmit($user));
    }

    public function test_late_submission_is_marked_as_late(): void
    {
        $assignment = Assignment::factory()->create([
            'is_published' => true,
            'available_from' => now()->subWeek(),
            'due_at' => now()->subDay(),
            'late_due_at' => now()->addDay(),
        ]);

        $submission = AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'status' => 'draft',
        ]);

        $submission->markAsSubmitted();

        $this->assertTrue($submission->is_late);
    }

    public function test_first_assignment_badge_is_awarded(): void
    {
        $user = User::factory()->create();

        AssignmentSubmission::factory()->submitted()->create([
            'user_id' => $user->id,
        ]);

        // Create badge AFTER submission so observer doesn't auto-award
        $badge = Badge::factory()->forFirstAssignment()->create();

        $this->assertTrue($badge->checkAndAward($user));
    }

    public function test_submission_grade_workflow(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $submission = AssignmentSubmission::factory()->submitted()->create();

        // AI grades the submission
        $grade = SubmissionGrade::factory()->aiGraded(85)->create([
            'submission_id' => $submission->id,
        ]);

        $this->assertEquals('pending', $grade->approval_status);
        $this->assertEquals(85, $grade->ai_score);

        // Teacher approves the grade
        $grade->approve();
        $grade->refresh();

        $this->assertEquals('approved', $grade->approval_status);
        $this->assertEquals(85, $grade->final_score);
    }

    public function test_submission_grade_modification(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $submission = AssignmentSubmission::factory()->submitted()->create();

        $grade = SubmissionGrade::factory()->aiGraded(85)->create([
            'submission_id' => $submission->id,
        ]);

        // Teacher modifies the grade
        $grade->modify(75, 'Reduced due to late submission');
        $grade->refresh();

        $this->assertEquals('modified', $grade->approval_status);
        $this->assertEquals(75, $grade->final_score);
        $this->assertEquals('Reduced due to late submission', $grade->final_feedback);
    }

    public function test_rubric_based_grading(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->structured()->create([
            'assignment_id' => $assignment->id,
        ]);

        RubricCriteria::factory()->create([
            'rubric_id' => $rubric->id,
            'max_points' => 40,
        ]);

        RubricCriteria::factory()->create([
            'rubric_id' => $rubric->id,
            'max_points' => 60,
        ]);

        $this->assertEquals(100, $rubric->getTotalPoints());
    }

    public function test_freeform_rubric_uses_assignment_max_points(): void
    {
        $assignment = Assignment::factory()->create(['max_points' => 100]);
        $rubric = Rubric::factory()->freeform('Grade based on creativity and effort')->create([
            'assignment_id' => $assignment->id,
        ]);

        $this->assertEquals(100, $rubric->getTotalPoints());
        $this->assertTrue($rubric->isFreeform());
    }
}
