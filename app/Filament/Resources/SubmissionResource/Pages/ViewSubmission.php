<?php

namespace App\Filament\Resources\SubmissionResource\Pages;

use App\Filament\Resources\SubmissionResource;
use App\Models\AssignmentSubmission;
use App\Services\LlmAssignmentGrader;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewSubmission extends ViewRecord implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SubmissionResource::class;

    public ?array $gradingData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('grade_with_ai')
                ->label('Grade with AI')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $grader = new LlmAssignmentGrader();
                    $result = $grader->gradeSubmission($this->record);
                    
                    if ($result) {
                        $this->record->refresh();
                        $this->fillForm();
                        $this->dispatch('graded');
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Grading')
                    ->schema([
                        Forms\Components\TextInput::make('final_score')
                            ->label('Final Score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn () => $this->record->assignment->max_points)
                            ->suffix(fn () => '/' . $this->record->assignment->max_points)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('final_feedback')
                            ->label('Feedback')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn () => $this->record->hasAiGrade()),
            ])
            ->statePath('gradingData');
    }

    protected function getFormStatePath(): string
    {
        return 'gradingData';
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Submission Details')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Student'),
                        TextEntry::make('assignment.title')
                            ->label('Assignment'),
                        TextEntry::make('attempt_number')
                            ->label('Attempt Number'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted' => 'info',
                                'grading' => 'warning',
                                'graded' => 'success',
                                'approved' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('submitted_at')
                            ->label('Submitted At')
                            ->dateTime(),
                        TextEntry::make('is_late')
                            ->label('Late Submission')
                            ->boolean()
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(2),

                Section::make('Submission Content')
                    ->schema([
                        TextEntry::make('content')
                            ->label('Text Submission')
                            ->visible(fn ($record) => $record->assignment->type === 'text' && $record->content)
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('file_name')
                            ->label('Submitted File')
                            ->visible(fn ($record) => in_array($record->assignment->type, ['file', 'code']) && $record->file_name)
                            ->formatStateUsing(fn ($state, $record) => $state . ' (' . number_format(filesize(storage_path('app/' . $record->file_path)) / 1024, 2) . ' KB)')
                            ->url(fn ($record) => $record->file_path ? route('filament.admin.resources.submissions.download', $record) : null)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ]),

                Section::make('AI Grading')
                    ->schema([
                        TextEntry::make('grade.ai_score')
                            ->label('AI Score')
                            ->formatStateUsing(fn ($state, $record) => $state ? $state . '/' . $record->assignment->max_points : 'Not graded yet')
                            ->color('warning')
                            ->weight(FontWeight::Bold)
                            ->visible(fn ($record) => $record->hasAiGrade()),
                        TextEntry::make('grade.ai_feedback')
                            ->label('AI Feedback')
                            ->html()
                            ->visible(fn ($record) => $record->hasAiGrade() && $record->grade->ai_feedback)
                            ->columnSpanFull(),
                        TextEntry::make('grade.ai_provider')
                            ->label('AI Provider')
                            ->badge()
                            ->visible(fn ($record) => $record->hasAiGrade()),
                        TextEntry::make('grade.ai_graded_at')
                            ->label('Graded At')
                            ->dateTime()
                            ->visible(fn ($record) => $record->hasAiGrade()),
                    ])
                    ->visible(fn ($record) => $record->hasAiGrade())
                    ->collapsible(),

                Section::make('Final Grade')
                    ->schema([
                        TextEntry::make('grade.final_score')
                            ->label('Final Score')
                            ->formatStateUsing(fn ($state, $record) => $state ? $state . '/' . $record->assignment->max_points : 'Not graded')
                            ->color('success')
                            ->weight(FontWeight::Bold)
                            ->visible(fn ($record) => $record->grade && $record->grade->final_score !== null),
                        TextEntry::make('grade.final_feedback')
                            ->label('Final Feedback')
                            ->html()
                            ->visible(fn ($record) => $record->grade && $record->grade->final_feedback)
                            ->columnSpanFull(),
                        TextEntry::make('grade.approval_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'modified' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('grade.grader.name')
                            ->label('Graded By')
                            ->visible(fn ($record) => $record->grade && $record->grade->graded_by),
                        TextEntry::make('grade.approved_at')
                            ->label('Approved At')
                            ->dateTime()
                            ->visible(fn ($record) => $record->grade && $record->grade->approved_at),
                    ])
                    ->visible(fn ($record) => $record->grade)
                    ->collapsible(),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $grade = $this->record->grade;
        
        if ($grade) {
            $data['final_score'] = $grade->final_score ?? $grade->ai_score;
            $data['final_feedback'] = $grade->final_feedback ?? $grade->ai_feedback;
        }

        return $data;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $submission = $this->record;
        
        $grade = $submission->grade;
        if (!$grade) {
            $grade = $submission->grade()->create([
                'ai_score' => null,
                'ai_feedback' => null,
            ]);
        }

        if (isset($data['final_score']) || isset($data['final_feedback'])) {
            $aiScore = $grade->ai_score;
            $aiFeedback = $grade->ai_feedback;
            
            $finalScore = $data['final_score'] ?? $aiScore;
            $finalFeedback = $data['final_feedback'] ?? $aiFeedback;

            if ($finalScore == $aiScore && $finalFeedback == $aiFeedback) {
                $grade->approve();
            } else {
                $grade->modify($finalScore, $finalFeedback);
            }
        }

        $this->record->refresh();
        $this->fillForm();
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve AI Grade')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->hasAiGrade() && $this->record->grade->isPending())
                ->action(function () {
                    $this->record->grade->approve();
                    $this->record->refresh();
                    $this->fillForm();
                }),
            Actions\Action::make('reject')
                ->label('Reject AI Grade')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->hasAiGrade() && $this->record->grade->isPending())
                ->action(function () {
                    $this->record->grade->reject();
                    $this->record->refresh();
                    $this->fillForm();
                }),
            Actions\Action::make('save')
                ->label('Save Grade')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }
}
