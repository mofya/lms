<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EnrollmentTrendsWidget extends ChartWidget
{
    protected ?string $heading = 'Enrollment Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $enrollments = DB::table('course_user')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Enrollments',
                    'data' => $enrollments->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $enrollments->pluck('date')->map(fn($date) => \Carbon\Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
