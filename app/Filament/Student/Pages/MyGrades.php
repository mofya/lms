<?php

namespace App\Filament\Student\Pages;

use App\Models\Course;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class MyGrades extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected string $view = 'filament.student.pages.my-grades';

    protected static ?string $navigationLabel = 'My Grades';

    protected static ?int $navigationSort = 30;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Grade::query()
                    ->where('user_id', auth()->id())
                    ->with('course')
            )
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('quiz_average')
                    ->label('Quiz Average')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : 'N/A')
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_average')
                    ->label('Assignment Average')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : 'N/A')
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('participation_score')
                    ->label('Participation')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : 'N/A')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_grade')
                    ->label('Final Grade')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : 'N/A')
                    ->weight('bold')
                    ->size('lg')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_quizzes')
                    ->label('Quizzes Completed')
                    ->formatStateUsing(fn ($record) => "{$record->completed_quizzes}/{$record->total_quizzes}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_assignments')
                    ->label('Assignments Completed')
                    ->formatStateUsing(fn ($record) => "{$record->completed_assignments}/{$record->total_assignments}")
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title')
                    ->preload(),
            ])
            ->defaultSort('final_grade', 'desc')
            ->paginated([10, 25, 50]);
    }
}
