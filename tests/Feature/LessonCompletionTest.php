<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_complete_lesson(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);

        $this->assertTrue($user->completedLessons->contains($lesson));
    }

    public function test_completing_lesson_enrolls_user_in_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);

        $this->assertTrue($user->courses->contains($course));
    }

    public function test_completing_lesson_awards_xp(): void
    {
        $user = User::factory()->create(['xp_points' => 0]);
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);
        $user->refresh();

        $this->assertEquals(XpService::XP_PER_LESSON, $user->xp_points);
    }

    public function test_completing_lesson_updates_streak(): void
    {
        $user = User::factory()->create([
            'current_streak' => 0,
            'last_activity_date' => null,
        ]);
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);
        $user->refresh();

        $this->assertEquals(1, $user->current_streak);
    }

    public function test_completing_all_lessons_issues_certificate(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson1 = Lesson::factory()->published()->atPosition(1)->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->published()->atPosition(2)->create(['course_id' => $course->id]);

        $this->actingAs($user);
        $course->students()->attach($user);

        $user->completeLesson($lesson1);
        $user->completeLesson($lesson2);

        $this->assertDatabaseHas('certificates', [
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_user_can_uncomplete_lesson(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);
        $this->assertTrue($user->completedLessons->contains($lesson));

        $user->uncompleteLesson($lesson);
        $user->refresh();

        $this->assertFalse($user->completedLessons->contains($lesson));
    }

    public function test_uncompleting_only_lesson_removes_course_enrollment(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);

        $user->completeLesson($lesson);
        $this->assertTrue($user->courses->contains($course));

        $user->uncompleteLesson($lesson);
        $user->refresh();

        $this->assertFalse($user->courses->contains($course));
    }

    public function test_course_completed_badge_is_awarded(): void
    {
        $badge = Badge::factory()->forCourseCompleted()->create();
        $user = User::factory()->create();
        $course = Course::factory()->published()->create();
        $lesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->actingAs($user);
        $course->students()->attach($user);

        $user->completeLesson($lesson);

        $this->assertTrue($user->badges()->where('badge_id', $badge->id)->exists());
    }
}
