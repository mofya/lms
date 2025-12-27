<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\AssignmentSubmission;
use App\Services\LlmAssignmentGrader;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SubmissionResource extends Resource
{
    protected static ?string $model = AssignmentSubmission::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $label = 'Assignment Submission';

    protected static ?string $pluralLabel = 'Assignment Submissions';

    protected static UnitEnum|string|null $navigationGroup = 'Course Content';

    protected static ?int $navigationSort = 25;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filter by assignment if provided
                if (request()->has('assignment_id')) {
                    $query->where('assignment_id', request()->get('assignment_id'));
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attempt_number')
                    ->label('Attempt')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'info',
                        'grading' => 'warning',
                        'graded' => 'success',
                        'approved' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade.ai_score')
                    ->label('AI Score')
                    ->formatStateUsing(fn ($state, $record) => $state ? $state . '/' . $record->assignment->max_points : '-')
                    ->color('warning'),
                Tables\Columns\TextColumn::make('grade.final_score')
                    ->label('Final Score')
                    ->formatStateUsing(fn ($state, $record) => $state ? $state . '/' . $record->assignment->max_points : '-')
                    ->color('success'),
                Tables\Columns\TextColumn::make('grade.approval_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'modified' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assignment_id')
                    ->label('Assignment')
                    ->relationship('assignment', 'title'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'grading' => 'Grading',
                        'graded' => 'Graded',
                        'approved' => 'Approved',
                    ]),
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->relationship('grade', 'approval_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'modified' => 'Modified',
                    ]),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View & Grade')
                    ->icon('heroicon-o-eye')
                    ->url(fn (AssignmentSubmission $record) => Pages\ViewSubmission::getUrl(['record' => $record])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('grade_with_ai')
                        ->label('Grade with AI')
                        ->icon('heroicon-o-sparkles')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $grader = new LlmAssignmentGrader();
                            foreach ($records as $submission) {
                                if ($submission->status === 'submitted' || $submission->status === 'draft') {
                                    $grader->gradeSubmission($submission);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('approve_ai_grades')
                        ->label('Approve AI Grades')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $submission) {
                                if ($submission->grade && $submission->grade->isPending()) {
                                    $submission->grade->approve();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject_ai_grades')
                        ->label('Reject AI Grades')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $submission) {
                                if ($submission->grade && $submission->grade->isPending()) {
                                    $submission->grade->reject();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
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
            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }
}
