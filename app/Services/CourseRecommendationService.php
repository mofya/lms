<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;

class CourseRecommendationService
{
    /**
     * Get AI-powered course recommendations for a user
     */
    public function getRecommendations(User $user, int $limit = 5): array
    {
        // Get user's completed courses and performance
        $completedCourses = $user->courses()->with('lecturer')->get();
        $grades = $user->grades()->with('course')->get();

        // Extract completed lecturers upfront to avoid N+1 in ranking
        $completedLecturerIds = $completedCourses->pluck('lecturer_id')->filter()->unique();

        // Build context for AI
        $context = $this->buildUserContext($user, $completedCourses, $grades);
        $context['completed_lecturer_ids'] = $completedLecturerIds;

        // Get all available courses (not enrolled) with counts to avoid N+1
        $availableCourses = Course::where('is_published', true)
            ->whereDoesntHave('students', fn ($q) => $q->where('users.id', $user->id))
            ->with('lecturer')
            ->withCount('students')
            ->get();

        if ($availableCourses->isEmpty()) {
            return [];
        }

        // Use AI to rank courses (or fallback to rule-based)
        $recommendations = $this->rankCourses($context, $availableCourses, $limit);

        return $recommendations;
    }

    protected function buildUserContext(User $user, $completedCourses, $grades): array
    {
        $context = [
            'completed_courses' => $completedCourses->pluck('title')->toArray(),
            'average_grade' => $grades->avg('final_grade'),
            'interests' => [],
        ];

        // Extract topics from completed courses
        foreach ($completedCourses as $course) {
            // Could extract keywords from course description/title
            $context['interests'][] = $course->title;
        }

        return $context;
    }

    protected function rankCourses(array $context, $courses, int $limit): array
    {
        // Simple rule-based ranking (can be enhanced with AI)
        $scoredCourses = [];

        // Use pre-loaded lecturer IDs from context
        $completedLecturerIds = $context['completed_lecturer_ids'] ?? collect();

        foreach ($courses as $course) {
            $score = 0;

            // Boost if lecturer matches previous courses (no N+1 - using pre-loaded data)
            if ($completedLecturerIds->contains($course->lecturer_id)) {
                $score += 10;
            }

            // Boost based on enrollment count (no N+1 - using withCount)
            $enrollmentCount = $course->students_count ?? 0;
            $score += min($enrollmentCount / 10, 5);

            // Boost if course has quizzes/assignments (more complete)
            $hasQuizzes = $course->quizzes()->published()->exists();
            $hasAssignments = $course->assignments()->where('is_published', true)->exists();

            if ($hasQuizzes) {
                $score += 3;
            }
            if ($hasAssignments) {
                $score += 3;
            }

            $scoredCourses[] = [
                'course' => $course,
                'score' => $score,
            ];
        }

        // Sort by score and return top courses
        usort($scoredCourses, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredCourses, 0, $limit);
    }

    /**
     * Get AI-powered recommendations using LLM (optional enhancement)
     */
    public function getAiRecommendations(User $user, int $limit = 5): array
    {
        $completedCourses = $user->courses()->pluck('title')->toArray();
        $availableCourses = Course::where('is_published', true)
            ->whereDoesntHave('students', fn ($q) => $q->where('users.id', $user->id))
            ->get();

        if (empty($completedCourses) || $availableCourses->isEmpty()) {
            return $this->getRecommendations($user, $limit); // Fallback to rule-based
        }

        $prompt = $this->buildRecommendationPrompt($completedCourses, $availableCourses);

        // Call AI (similar to study assistant)
        // For now, fallback to rule-based
        return $this->getRecommendations($user, $limit);
    }

    protected function buildRecommendationPrompt(array $completedCourses, $availableCourses): string
    {
        $courseList = $availableCourses->pluck('title')->implode(', ');

        $prompt = 'Based on these completed courses: '.implode(', ', $completedCourses)."\n";
        $prompt .= "Recommend the most relevant courses from: {$courseList}\n";
        $prompt .= 'Return a JSON array of course titles in order of relevance.';

        return $prompt;
    }
}
