<?php

namespace Tests\Unit\Models;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Discussion;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_belongs_to_lecturer(): void
    {
        $lecturer = User::factory()->admin()->create();
        $course = Course::factory()->forLecturer($lecturer)->create();

        $this->assertTrue($course->lecturer->is($lecturer));
    }

    public function test_course_has_many_students(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->create();

        $course->students()->attach($student);

        $this->assertTrue($course->students->contains($student));
    }

    public function test_course_has_many_lessons(): void
    {
        $course = Course::factory()->create();
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->lessons->contains($lesson));
    }

    public function test_course_has_many_quizzes(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->quizzes->contains($quiz));
    }

    public function test_course_has_many_assignments(): void
    {
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->assignments->contains($assignment));
    }

    public function test_course_has_many_grades(): void
    {
        $course = Course::factory()->create();
        $grade = Grade::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->grades->contains($grade));
    }

    public function test_course_has_many_announcements(): void
    {
        $course = Course::factory()->create();
        $announcement = Announcement::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->announcements->contains($announcement));
    }

    public function test_course_has_many_discussions(): void
    {
        $course = Course::factory()->create();
        $discussion = Discussion::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->discussions->contains($discussion));
    }

    public function test_course_has_many_certificates(): void
    {
        $course = Course::factory()->create();
        $certificate = Certificate::factory()->create(['course_id' => $course->id]);

        $this->assertTrue($course->certificates->contains($certificate));
    }

    public function test_published_scope_filters_correctly(): void
    {
        Course::factory()->create(['is_published' => false]);
        $publishedCourse = Course::factory()->published()->create();

        $published = Course::published()->get();

        $this->assertCount(1, $published);
        $this->assertTrue($published->contains($publishedCourse));
    }

    public function test_published_lessons_only_returns_published(): void
    {
        $course = Course::factory()->create();
        Lesson::factory()->create(['course_id' => $course->id, 'is_published' => false]);
        $publishedLesson = Lesson::factory()->published()->create(['course_id' => $course->id]);

        $this->assertCount(1, $course->publishedLessons);
        $this->assertTrue($course->publishedLessons->contains($publishedLesson));
    }

    public function test_lessons_are_ordered_by_position(): void
    {
        $course = Course::factory()->create();
        $lesson3 = Lesson::factory()->atPosition(3)->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->atPosition(1)->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->atPosition(2)->create(['course_id' => $course->id]);

        $lessons = $course->lessons;

        $this->assertEquals($lesson1->id, $lessons[0]->id);
        $this->assertEquals($lesson2->id, $lessons[1]->id);
        $this->assertEquals($lesson3->id, $lessons[2]->id);
    }

    public function test_description_accessor_handles_json(): void
    {
        $course = Course::factory()->create();
        $jsonDescription = [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Test']]]
            ]
        ];

        $course->description = $jsonDescription;
        $course->save();
        $course->refresh();

        $this->assertIsArray($course->description);
        $this->assertEquals('doc', $course->description['type']);
    }

    public function test_description_accessor_handles_plain_text(): void
    {
        $course = new Course();

        // Use setRawAttributes to simulate plain text from database
        $course->setRawAttributes([
            'title' => 'Test',
            'lecturer_id' => User::factory()->admin()->create()->id,
            'description' => 'Plain text description',
        ]);

        $description = $course->description;

        $this->assertIsArray($description);
        $this->assertEquals('doc', $description['type']);
    }
}
