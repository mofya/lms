<?php

namespace App\Filament\Student\Resources;

use BackedEnum;
use App\Filament\Student\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Assignments';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show assignments for courses the student is enrolled in
                $query->whereHas('course.students', function ($q) {
                    $q->where('users.id', auth()->id());
                })
                ->published()
                ->where(function ($q) {
                    $q->whereNull('available_from')
                      ->orWhere('available_from', '<=', now());
                });
            })
            ->columns([
                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Assignment $record) => $record->description ? strip_tags($record->description) : null),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'info',
                        'file' => 'warning',
                        'code' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : null),
                TextColumn::make('max_points')
                    ->label('Points')
                    ->suffix(' pts'),
                TextColumn::make('submission_status')
                    ->label('Status')
                    ->getStateUsing(function (Assignment $record) {
                        $submission = $record->submissions()
                            ->where('user_id', auth()->id())
                            ->where('status', '!=', 'draft')
                            ->latest()
                            ->first();

                        if (!$submission) {
                            return 'Not Submitted';
                        }

                        return match ($submission->status) {
                            'submitted' => 'Submitted',
                            'grading' => 'Grading',
                            'graded' => 'Graded',
                            'approved' => 'Graded',
                            default => 'Unknown',
                        };
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Not Submitted' => 'gray',
                        'Submitted' => 'info',
                        'Grading' => 'warning',
                        'Graded' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->relationship('course', 'title')
                    ->label('Course'),
            ])
            ->defaultSort('due_at', 'asc');
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
            'index' => Pages\ListAssignments::route('/'),
            'view' => Pages\ViewAssignment::route('/{record}'),
        ];
    }
}
