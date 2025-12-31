<?php

namespace Tests\Unit\Models;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignment_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($assignment->course->is($course));
    }

    public function test_assignment_has_one_rubric(): void
    {
        $assignment = Assignment::factory()->create();
        $rubric = Rubric::factory()->create(['assignment_id' => $assignment->id]);

        $this->assertTrue($assignment->rubric->is($rubric));
    }

    public function test_assignment_has_many_submissions(): void
    {
        $assignment = Assignment::factory()->create();
        $submission = AssignmentSubmission::factory()->create(['assignment_id' => $assignment->id]);

        $this->assertTrue($assignment->submissions->contains($submission));
    }

    public function test_published_scope_filters_correctly(): void
    {
        Assignment::factory()->create(['is_published' => false]);
        $publishedAssignment = Assignment::factory()->published()->create();

        $published = Assignment::published()->get();

        $this->assertCount(1, $published);
        $this->assertTrue($published->contains($publishedAssignment));
    }

    public function test_is_available_returns_true_when_published_and_after_available_from(): void
    {
        $assignment = Assignment::factory()->available()->create();

        $this->assertTrue($assignment->isAvailable());
    }

    public function test_is_available_returns_false_when_not_published(): void
    {
        $assignment = Assignment::factory()->create(['is_published' => false]);

        $this->assertFalse($assignment->isAvailable());
    }

    public function test_is_available_returns_false_before_available_from(): void
    {
        $assignment = Assignment::factory()->create([
            'is_published' => true,
            'available_from' => now()->addDay(),
        ]);

        $this->assertFalse($assignment->isAvailable());
    }

    public function test_is_overdue_returns_true_after_due_date(): void
    {
        $assignment = Assignment::factory()->pastDue()->create();

        $this->assertTrue($assignment->isOverdue());
    }

    public function test_is_overdue_returns_false_before_due_date(): void
    {
        $assignment = Assignment::factory()->available()->create();

        $this->assertFalse($assignment->isOverdue());
    }

    public function test_is_overdue_respects_late_due_at(): void
    {
        $assignment = Assignment::factory()->withLatePeriod()->create();

        // Within late period - not overdue yet
        $this->assertFalse($assignment->isOverdue());
    }

    public function test_check_if_late_returns_true_after_due_date(): void
    {
        $assignment = Assignment::factory()->pastDue()->create();

        $this->assertTrue($assignment->checkIfLate());
    }

    public function test_can_submit_returns_true_when_conditions_met(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->available()->create(['max_submissions' => 3]);

        $this->assertTrue($assignment->canSubmit($user));
    }

    public function test_can_submit_returns_false_when_not_available(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->create(['is_published' => false]);

        $this->assertFalse($assignment->canSubmit($user));
    }

    public function test_can_submit_returns_false_when_overdue(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->pastDue()->create();

        $this->assertFalse($assignment->canSubmit($user));
    }

    public function test_can_submit_returns_false_when_max_submissions_reached(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->available()->create(['max_submissions' => 1]);

        // Create a submitted submission
        AssignmentSubmission::factory()->submitted()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
        ]);

        $this->assertFalse($assignment->canSubmit($user));
    }

    public function test_can_submit_ignores_draft_submissions_in_count(): void
    {
        $user = User::factory()->create();
        $assignment = Assignment::factory()->available()->create(['max_submissions' => 1]);

        // Create a draft submission - should not count
        AssignmentSubmission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $this->assertTrue($assignment->canSubmit($user));
    }
}
