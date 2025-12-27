<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CoursePerformanceWidget extends ChartWidget
{
    protected ?string $heading = 'Top Performing Courses';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $topCourses = DB::table('grades')
            ->join('courses', 'grades.course_id', '=', 'courses.id')
            ->select('courses.title', DB::raw('AVG(grades.final_grade) as avg_grade'))
            ->whereNotNull('grades.final_grade')
            ->groupBy('courses.id', 'courses.title')
            ->orderBy('avg_grade', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Average Grade (%)',
                    'data' => $topCourses->pluck('avg_grade')->toArray(),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                    ],
                ],
            ],
            'labels' => $topCourses->pluck('title')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
