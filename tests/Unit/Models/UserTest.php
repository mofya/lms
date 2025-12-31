<?php

namespace Tests\Unit\Models;

use App\Models\Announcement;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_teaching_courses_relationship(): void
    {
        $lecturer = User::factory()->admin()->create();
        $course = Course::factory()->forLecturer($lecturer)->create();

        $this->assertTrue($lecturer->teachingCourses->contains($course));
    }

    public function test_user_has_enrolled_courses_relationship(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $student->enrolledCourses()->attach($course);

        $this->assertTrue($student->enrolledCourses->contains($course));
    }

    public function test_user_has_completed_lessons_relationship(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $student->completedLessons()->attach($lesson);

        $this->assertTrue($student->completedLessons->contains($lesson));
    }

    public function test_user_has_grades_relationship(): void
    {
        $student = User::factory()->create();
        $grade = Grade::factory()->create(['user_id' => $student->id]);

        $this->assertTrue($student->grades->contains($grade));
    }

    public function test_user_has_announcements_relationship(): void
    {
        $user = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($user->announcements->contains($announcement));
    }

    public function test_user_has_certificates_relationship(): void
    {
        $user = User::factory()->create();
        $certificate = Certificate::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->certificates->contains($certificate));
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $panel = $this->createMock(\Filament\Panel::class);
        $panel->method('getId')->willReturn('admin');

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create();

        $panel = $this->createMock(\Filament\Panel::class);
        $panel->method('getId')->willReturn('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_any_user_can_access_student_panel(): void
    {
        $user = User::factory()->create();

        $panel = $this->createMock(\Filament\Panel::class);
        $panel->method('getId')->willReturn('student');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_gamification_fields_have_correct_defaults(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->xp_points);
        $this->assertEquals(1, $user->level);
        $this->assertEquals(0, $user->current_streak);
        $this->assertNull($user->last_activity_date);
    }

    public function test_user_factory_with_xp_calculates_level(): void
    {
        $user = User::factory()->withXp(250)->create();

        $this->assertEquals(250, $user->xp_points);
        $this->assertEquals(3, $user->level); // 250/100 + 1 = 3
    }

    public function test_user_factory_with_streak_sets_last_activity(): void
    {
        $user = User::factory()->withStreak(7)->create();

        $this->assertEquals(7, $user->current_streak);
        $this->assertNotNull($user->last_activity_date);
    }
}
