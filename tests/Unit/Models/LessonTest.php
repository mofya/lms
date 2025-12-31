<?php

namespace Tests\Unit\Models;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_belongs_to_course(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($lesson->course->is($course));
    }

    public function test_published_scope_filters_correctly(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->create(['course_id' => $course->id, 'is_published' => false]);
        $publishedLesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $published = Lesson::published()->get();

        $this->assertTrue($published->contains($publishedLesson));
        $this->assertCount(1, $published);
    }

    public function test_get_next_returns_next_lesson(): void
    {
        $course = Course::factory()->create();
        $lesson1 = Lesson::factory()->published()->atPosition(1)->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->published()->atPosition(2)->create(['course_id' => $course->id]);
        $lesson3 = Lesson::factory()->published()->atPosition(3)->create(['course_id' => $course->id]);

        $this->assertTrue($lesson1->getNext()->is($lesson2));
        $this->assertTrue($lesson2->getNext()->is($lesson3));
    }

    public function test_get_next_returns_null_for_last_lesson(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->published()->atPosition(1)->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->published()->atPosition(2)->create(['course_id' => $course->id]);

        $this->assertNull($lesson2->getNext());
    }

    public function test_get_previous_returns_previous_lesson(): void
    {
        $course = Course::factory()->create();
        $lesson1 = Lesson::factory()->published()->atPosition(1)->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->published()->atPosition(2)->create(['course_id' => $course->id]);
        $lesson3 = Lesson::factory()->published()->atPosition(3)->create(['course_id' => $course->id]);

        $this->assertTrue($lesson2->getPrevious()->is($lesson1));
        $this->assertTrue($lesson3->getPrevious()->is($lesson2));
    }

    public function test_get_previous_returns_null_for_first_lesson(): void
    {
        $course = Course::factory()->create();
        $lesson1 = Lesson::factory()->published()->atPosition(1)->create(['course_id' => $course->id]);
        Lesson::factory()->published()->atPosition(2)->create(['course_id' => $course->id]);

        $this->assertNull($lesson1->getPrevious());
    }

    public function test_lesson_type_defaults_to_text(): void
    {
        $lesson = new Lesson();

        $this->assertEquals(Lesson::TYPE_TEXT, $lesson->type);
    }

    public function test_video_lesson_has_video_attributes(): void
    {
        $lesson = Lesson::factory()->video('https://example.com/video.mp4')->create();

        $this->assertEquals(Lesson::TYPE_VIDEO, $lesson->type);
        $this->assertEquals('https://example.com/video.mp4', $lesson->video_url);
        $this->assertNotNull($lesson->duration_seconds);
    }
}
