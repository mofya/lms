<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\User;
use App\Models\Test;
use App\Models\AssignmentSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStudents = User::where('is_admin', false)->count();
        $totalCourses = Course::where('is_published', true)->count();
        
        $enrolledStudents = DB::table('course_user')
            ->distinct('user_id')
            ->count('user_id');
        
        $completionRate = $this->getAverageCompletionRate();
        $averageGrade = $this->getAverageGrade();

        return [
            Stat::make('Total Students', $totalStudents)
                ->description('Registered students')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            
            Stat::make('Published Courses', $totalCourses)
                ->description('Active courses')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('info'),
            
            Stat::make('Enrolled Students', $enrolledStudents)
                ->description('Students with enrollments')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
            
            Stat::make('Average Completion Rate', number_format($completionRate, 1) . '%')
                ->description('Lesson completion across all courses')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Average Grade', $averageGrade !== null ? number_format($averageGrade, 1) . '%' : 'N/A')
                ->description('Overall student performance')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($averageGrade >= 70 ? 'success' : 'warning'),
        ];
    }

    protected function getAverageCompletionRate(): float
    {
        $totalLessons = DB::table('lessons')
            ->where('is_published', true)
            ->count();
        
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = DB::table('lesson_user')->count();
        $totalEnrollments = DB::table('course_user')->count();

        if ($totalEnrollments === 0) {
            return 0;
        }

        // Average completion rate per enrollment
        return ($completedLessons / ($totalEnrollments * $totalLessons)) * 100;
    }

    protected function getAverageGrade(): ?float
    {
        return DB::table('grades')
            ->whereNotNull('final_grade')
            ->avg('final_grade');
    }
}
