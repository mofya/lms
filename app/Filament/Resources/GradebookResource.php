<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\GradebookResource\Pages;
use App\Models\Grade;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GradebookResource extends Resource
{
    protected static ?string $model = Grade::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $label = 'Gradebook';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quiz_average')
                    ->label('Quiz Avg')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : '-')
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_average')
                    ->label('Assignment Avg')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : '-')
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('participation_score')
                    ->label('Participation')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : '-')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_grade')
                    ->label('Final Grade')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) . '%' : '-')
                    ->weight('bold')
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 80 => 'info',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_quizzes')
                    ->label('Quizzes')
                    ->formatStateUsing(fn ($record) => "{$record->completed_quizzes}/{$record->total_quizzes}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_assignments')
                    ->label('Assignments')
                    ->formatStateUsing(fn ($record) => "{$record->completed_assignments}/{$record->total_assignments}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('calculated_at')
                    ->label('Last Calculated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Student')
                    ->relationship('user', 'name')
                    ->searchable(),
            ])
            ->actions([
                Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Grade $record) {
                        $record->recalculate();
                        \Filament\Notifications\Notification::make()
                            ->title('Grade recalculated successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('recalculate_all')
                    ->label('Recalculate Selected')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Collection $records) {
                        foreach ($records as $grade) {
                            $grade->recalculate();
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Grades recalculated')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('final_grade', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGrades::route('/'),
        ];
    }
}
