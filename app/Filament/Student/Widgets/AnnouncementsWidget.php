<?php

namespace App\Filament\Student\Widgets;

use App\Models\Announcement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class AnnouncementsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        $enrolledCourseIds = Auth::user()->courses()->pluck('courses.id');

        return $table
            ->query(
                Announcement::query()
                    ->published()
                    ->where(function ($q) use ($enrolledCourseIds) {
                        $q->whereNull('course_id')
                          ->orWhereIn('course_id', $enrolledCourseIds);
                    })
                    ->orderBy('is_pinned', 'desc')
                    ->orderBy('published_at', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->limit(40)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->default('System-wide')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->heading('Recent Announcements')
            ->paginated(false);
    }
}
