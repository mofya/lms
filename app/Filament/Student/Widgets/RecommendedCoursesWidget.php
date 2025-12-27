<?php

namespace App\Filament\Student\Widgets;

use App\Services\CourseRecommendationService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class RecommendedCoursesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $service = new CourseRecommendationService();
        $recommendations = $service->getRecommendations(Auth::user(), 5);

        return $table
            ->query(
                \App\Models\Course::whereIn('id', collect($recommendations)->pluck('course.id')->toArray())
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('lecturer.name')
                    ->label('Instructor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('students_count')
                    ->label('Enrolled')
                    ->counts('students')
                    ->badge(),
            ])
            ->heading('Recommended for You')
            ->paginated(false);
    }
}
