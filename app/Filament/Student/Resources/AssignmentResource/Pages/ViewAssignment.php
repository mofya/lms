<?php

namespace App\Filament\Student\Resources\AssignmentResource\Pages;

use App\Filament\Student\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ViewAssignment extends ViewRecord
{
    protected static string $resource = AssignmentResource::class;

    public ?array $submissionData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignment = $this->record;
        $submission = $assignment->submissions()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($submission) {
            $this->submissionData = [
                'content' => $submission->content,
                'file' => $submission->file_path ? Storage::url($submission->file_path) : null,
            ];
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $assignment = $this->record;

        return $schema
            ->schema([
                Section::make('Submission')
                    ->schema([
                        Forms\Components\Textarea::make('content')
                            ->label('Your Answer')
                            ->rows(10)
                            ->required(fn () => $assignment->type === 'text')
                            ->visible(fn () => $assignment->type === 'text')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file')
                            ->label('Upload File')
                            ->acceptedFileTypes($assignment->allowed_file_types)
                            ->maxSize($assignment->max_file_size_mb * 1024)
                            ->disk('local')
                            ->directory('assignment-submissions')
                            ->required(fn () => in_array($assignment->type, ['file', 'code']))
                            ->visible(fn () => in_array($assignment->type, ['file', 'code']))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('submissionData');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Assignment Details')
                    ->schema([
                        TextEntry::make('title')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('instructions')
                            ->label('Instructions')
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'text' => 'info',
                                'file' => 'warning',
                                'code' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('max_points')
                            ->label('Points')
                            ->suffix(' pts'),
                        TextEntry::make('due_at')
                            ->label('Due Date')
                            ->dateTime()
                            ->color(fn ($state) => $state && now()->gt($state) ? 'danger' : null),
                        TextEntry::make('late_due_at')
                            ->label('Late Deadline')
                            ->dateTime()
                            ->visible(fn (Assignment $record) => $record->late_due_at !== null),
                    ])
                    ->columns(2),

                Section::make('My Submissions')
                    ->schema([
                        TextEntry::make('submissions')
                            ->label('')
                            ->formatStateUsing(function (Assignment $record) {
                                $submissions = $record->submissions()
                                    ->where('user_id', auth()->id())
                                    ->orderBy('attempt_number', 'desc')
                                    ->get();

                                if ($submissions->isEmpty()) {
                                    return 'No submissions yet.';
                                }

                                $html = '<div class="space-y-4">';
                                foreach ($submissions as $submission) {
                                    $html .= '<div class="border rounded p-4">';
                                    $html .= '<div class="flex justify-between items-start mb-2">';
                                    $html .= '<span class="font-semibold">Attempt #' . $submission->attempt_number . '</span>';
                                    $html .= '<span class="badge badge-' . match ($submission->status) {
                                        'draft' => 'gray',
                                        'submitted' => 'info',
                                        'grading' => 'warning',
                                        'graded' => 'success',
                                        'approved' => 'success',
                                        default => 'gray',
                                    } . '">' . ucfirst($submission->status) . '</span>';
                                    $html .= '</div>';
                                    $html .= '<div class="text-sm text-gray-600">Submitted: ' . $submission->submitted_at?->format('M j, Y H:i') . '</div>';
                                    
                                    if ($submission->grade) {
                                        $html .= '<div class="mt-2">';
                                        if ($submission->grade->final_score !== null) {
                                            $html .= '<div class="font-semibold">Score: ' . $submission->grade->final_score . '/' . $record->max_points . '</div>';
                                        } elseif ($submission->grade->ai_score !== null) {
                                            $html .= '<div class="font-semibold text-warning">AI Score: ' . $submission->grade->ai_score . '/' . $record->max_points . ' (Pending Approval)</div>';
                                        }
                                        if ($submission->grade->final_feedback || $submission->grade->ai_feedback) {
                                            $html .= '<div class="mt-2">' . ($submission->grade->final_feedback ?? $submission->grade->ai_feedback) . '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $assignment = $this->record;
        $canSubmit = $assignment->canSubmit(auth()->user());

        return [
            Actions\Action::make('submit')
                ->label('Submit Assignment')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible($canSubmit)
                ->requiresConfirmation()
                ->action(function () {
                    $data = $this->form->getState();
                    $assignment = $this->record;

                    // Get current submission count
                    $attemptNumber = $assignment->submissions()
                        ->where('user_id', auth()->id())
                        ->max('attempt_number') ?? 0;
                    $attemptNumber++;

                    $filePath = null;
                    $fileName = null;
                    if (isset($data['file']) && $data['file']) {
                        $filePath = $data['file'];
                        $fileName = basename($filePath);
                    }

                    $submission = AssignmentSubmission::create([
                        'assignment_id' => $assignment->id,
                        'user_id' => auth()->id(),
                        'attempt_number' => $attemptNumber,
                        'content' => $data['content'] ?? null,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'is_late' => $assignment->due_at && now()->gt($assignment->due_at),
                    ]);

                    $submission->markAsSubmitted();

                    $this->fillForm();
                    $this->dispatch('submitted');
                }),
        ];
    }

    protected function getFormActions(): array
    {
        $assignment = $this->record;
        $canSubmit = $assignment->canSubmit(auth()->user());

        if (!$canSubmit) {
            return [];
        }

        return [
            Actions\Action::make('save_draft')
                ->label('Save Draft')
                ->action(function () {
                    $data = $this->form->getState();
                    $assignment = $this->record;

                    $submission = $assignment->submissions()
                        ->where('user_id', auth()->id())
                        ->where('status', 'draft')
                        ->latest()
                        ->first();

                    if (!$submission) {
                        $attemptNumber = $assignment->submissions()
                            ->where('user_id', auth()->id())
                            ->max('attempt_number') ?? 0;
                        $attemptNumber++;

                        $submission = AssignmentSubmission::create([
                            'assignment_id' => $assignment->id,
                            'user_id' => auth()->id(),
                            'attempt_number' => $attemptNumber,
                            'status' => 'draft',
                        ]);
                    }

                    $filePath = null;
                    $fileName = null;
                    if (isset($data['file']) && $data['file']) {
                        $filePath = $data['file'];
                        $fileName = basename($filePath);
                    }

                    $submission->update([
                        'content' => $data['content'] ?? null,
                        'file_path' => $filePath,
                        'file_name' => $fileName,
                    ]);
                }),
        ];
    }
}
