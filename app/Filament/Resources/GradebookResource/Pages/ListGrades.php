<?php

namespace App\Filament\Resources\GradebookResource\Pages;

use App\Filament\Resources\GradebookResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ExportBulkAction;

class ListGrades extends ListRecords
{
    protected static string $resource = GradebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recalculate_all')
                ->label('Recalculate All Grades')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $grades = \App\Models\Grade::all();
                    foreach ($grades as $grade) {
                        $grade->recalculate();
                    }
                    \Filament\Notifications\Notification::make()
                        ->title('All grades recalculated')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('export')
                ->label('Export Grades')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return $this->exportGrades();
                }),
        ];
    }

    protected function exportGrades()
    {
        $grades = \App\Models\Grade::with(['user', 'course'])->get();
        
        $filename = 'grades_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($grades) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Student Name',
                'Email',
                'Course',
                'Quiz Average (%)',
                'Assignment Average (%)',
                'Participation (%)',
                'Final Grade (%)',
                'Quizzes Completed',
                'Assignments Completed',
                'Last Calculated'
            ]);

            // Data
            foreach ($grades as $grade) {
                fputcsv($file, [
                    $grade->user->name,
                    $grade->user->email,
                    $grade->course->title,
                    $grade->quiz_average ?? 'N/A',
                    $grade->assignment_average ?? 'N/A',
                    $grade->participation_score ?? 'N/A',
                    $grade->final_grade ?? 'N/A',
                    $grade->completed_quizzes . '/' . $grade->total_quizzes,
                    $grade->completed_assignments . '/' . $grade->total_assignments,
                    $grade->calculated_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
