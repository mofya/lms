<?php

namespace Tests\Unit\Services;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use App\Services\CourseRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseRecommendationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseRecommendationService;
    }

    public function test_get_recommendations_returns_empty_array_when_no_courses_available(): void
    {
        $user = User::factory()->create();

        $recommendations = $this->service->getRecommendations($user);

        $this->assertIsArray($recommendations);
        $this->assertEmpty($recommendations);
    }

    public function test_get_recommendations_excludes_enrolled_courses(): void
    {
        $user = User::factory()->create();
        $enrolledCourse = Course::factory()->create(['is_published' => true]);
        $availableCourse = Course::factory()->create(['is_published' => true]);

        $enrolledCourse->students()->attach($user->id);

        $recommendations = $this->service->getRecommendations($user);

        $courseIds = collect($recommendations)->pluck('course.id')->toArray();

        $this->assertNotContains($enrolledCourse->id, $courseIds);
        $this->assertContains($availableCourse->id, $courseIds);
    }

    public function test_get_recommendations_excludes_unpublished_courses(): void
    {
        $user = User::factory()->create();
        $publishedCourse = Course::factory()->create(['is_published' => true]);
        $unpublishedCourse = Course::factory()->create(['is_published' => false]);

        $recommendations = $this->service->getRecommendations($user);

        $courseIds = collect($recommendations)->pluck('course.id')->toArray();

        $this->assertContains($publishedCourse->id, $courseIds);
        $this->assertNotContains($unpublishedCourse->id, $courseIds);
    }

    public function test_get_recommendations_respects_limit(): void
    {
        $user = User::factory()->create();
        Course::factory()->count(10)->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user, 3);

        $this->assertCount(3, $recommendations);
    }

    public function test_get_recommendations_boosts_courses_from_same_lecturer(): void
    {
        $user = User::factory()->create();
        $lecturer = User::factory()->create();

        $completedCourse = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'is_published' => true,
        ]);
        $completedCourse->students()->attach($user->id);

        $sameLecturerCourse = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'is_published' => true,
        ]);

        $differentLecturerCourse = Course::factory()->create([
            'is_published' => true,
        ]);

        $recommendations = $this->service->getRecommendations($user);

        $this->assertNotEmpty($recommendations);

        $sameLecturerRec = collect($recommendations)->firstWhere('course.id', $sameLecturerCourse->id);
        $differentLecturerRec = collect($recommendations)->firstWhere('course.id', $differentLecturerCourse->id);

        if ($sameLecturerRec && $differentLecturerRec) {
            $this->assertGreaterThan($differentLecturerRec['score'], $sameLecturerRec['score']);
        }
    }

    public function test_get_recommendations_boosts_courses_with_quizzes(): void
    {
        $user = User::factory()->create();

        $courseWithQuiz = Course::factory()->create(['is_published' => true]);
        Quiz::factory()->create([
            'course_id' => $courseWithQuiz->id,
            'is_published' => true,
        ]);

        $courseWithoutQuiz = Course::factory()->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user);

        $withQuizRec = collect($recommendations)->firstWhere('course.id', $courseWithQuiz->id);
        $withoutQuizRec = collect($recommendations)->firstWhere('course.id', $courseWithoutQuiz->id);

        $this->assertNotNull($withQuizRec);
        $this->assertNotNull($withoutQuizRec);
        $this->assertGreaterThan($withoutQuizRec['score'], $withQuizRec['score']);
    }

    public function test_get_recommendations_boosts_courses_with_assignments(): void
    {
        $user = User::factory()->create();

        $courseWithAssignment = Course::factory()->create(['is_published' => true]);
        Assignment::factory()->create([
            'course_id' => $courseWithAssignment->id,
            'is_published' => true,
        ]);

        $courseWithoutAssignment = Course::factory()->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user);

        $withAssignmentRec = collect($recommendations)->firstWhere('course.id', $courseWithAssignment->id);
        $withoutAssignmentRec = collect($recommendations)->firstWhere('course.id', $courseWithoutAssignment->id);

        $this->assertNotNull($withAssignmentRec);
        $this->assertNotNull($withoutAssignmentRec);
        $this->assertGreaterThan($withoutAssignmentRec['score'], $withAssignmentRec['score']);
    }

    public function test_get_recommendations_boosts_popular_courses(): void
    {
        $user = User::factory()->create();

        $popularCourse = Course::factory()->create(['is_published' => true]);
        $students = User::factory()->count(50)->create();
        $popularCourse->students()->attach($students->pluck('id'));

        $unpopularCourse = Course::factory()->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user);

        $popularRec = collect($recommendations)->firstWhere('course.id', $popularCourse->id);
        $unpopularRec = collect($recommendations)->firstWhere('course.id', $unpopularCourse->id);

        $this->assertNotNull($popularRec);
        $this->assertNotNull($unpopularRec);
        $this->assertGreaterThan($unpopularRec['score'], $popularRec['score']);
    }

    public function test_get_recommendations_returns_sorted_by_score_descending(): void
    {
        $user = User::factory()->create();

        Course::factory()->count(5)->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user);

        $scores = collect($recommendations)->pluck('score')->toArray();
        $sortedScores = $scores;
        rsort($sortedScores);

        $this->assertEquals($sortedScores, $scores);
    }

    public function test_get_ai_recommendations_falls_back_to_rule_based(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['is_published' => true]);

        $recommendations = $this->service->getAiRecommendations($user);

        $this->assertIsArray($recommendations);
    }

    public function test_get_ai_recommendations_falls_back_when_no_completed_courses(): void
    {
        $user = User::factory()->create();
        Course::factory()->count(3)->create(['is_published' => true]);

        $recommendations = $this->service->getAiRecommendations($user);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
    }

    public function test_recommendation_structure_contains_course_and_score(): void
    {
        $user = User::factory()->create();
        Course::factory()->create(['is_published' => true]);

        $recommendations = $this->service->getRecommendations($user);

        $this->assertNotEmpty($recommendations);
        $this->assertArrayHasKey('course', $recommendations[0]);
        $this->assertArrayHasKey('score', $recommendations[0]);
        $this->assertInstanceOf(Course::class, $recommendations[0]['course']);
        $this->assertIsNumeric($recommendations[0]['score']);
    }
}
