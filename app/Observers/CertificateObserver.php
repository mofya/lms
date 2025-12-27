<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\User;
use App\Services\CertificateGenerator;

class CertificateObserver
{
    /**
     * Check if user completed course and issue certificate
     */
    public static function checkCourseCompletion(User $user, Course $course): void
    {
        // Check if all lessons are completed
        $totalLessons = $course->publishedLessons()->count();
        $completedLessons = $user->completedLessons()
            ->where('course_id', $course->id)
            ->count();

        // Check if user has passing grade (if grade exists)
        $grade = \App\Models\Grade::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $hasPassingGrade = $grade && $grade->final_grade && $grade->final_grade >= 70;

        // Issue certificate if course is completed and has passing grade (or no grade requirement)
        if ($totalLessons > 0 && $completedLessons >= $totalLessons && ($hasPassingGrade || !$grade)) {
            $generator = new CertificateGenerator();
            $generator->generateCertificate($user, $course);
        }
    }
}
