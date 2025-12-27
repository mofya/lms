<?php

namespace App\Filament\Student\Resources\StudentResultResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Student\Resources\StudentResultResource;
use App\Models\Test;
use Filament\Actions;
use Filament\Infolists;
use App\Models\TestAnswer;
use App\Models\QuestionOption;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\FontWeight;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentResult extends ViewRecord
{
    protected static string $resource = StudentResultResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load('testAnswers.question.questionOptions');

        $this->authorizeAccess();

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->columns(1)
                    ->schema([
                        TextEntry::make('created_at')
                            ->inlineLabel()
                            ->label('Date'),
                        TextEntry::make('result')
                            ->inlineLabel()
                            ->formatStateUsing(function (Test $record) {
                                return $record->result . '/' . $record->questions->count() . ' (time: ' . intval($record->time_spent / 60) . ':' . gmdate('s', $record->time_spent) . ' minutes)';
                            }),
                    ]),

                RepeatableEntry::make('testAnswers')
                    ->label('Questions')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('question.question_text')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->weight(FontWeight::Bold),
                        RepeatableEntry::make('question.questionOptions')
                            ->inlineLabel()
                            ->contained(false)
                            ->schema([
                                TextEntry::make('option')
                                    ->html()
                                    ->hiddenLabel()
                                    ->weight(fn (QuestionOption $record) => $record->correct ? FontWeight::Bold : null)
                                    ->formatStateUsing(function (QuestionOption $record) {
                                        $answer = static::getRecord()->testAnswers->firstWhere(function (TestAnswer $value) use ($record) {
                                            return $value->question_id === $record->question_id;
                                        });

                                        return $record->option . ' ' .
                                            ($record->correct ? new HtmlString('<span class="italic">(correct answer)</span>') : null) . ' ' .
                                            ($answer->option_id == $record->id ? new HtmlString('<span class="italic">(your answer)</span>') : null);
                                    }),
                            ]),
                        TextEntry::make('question.code_snippet')
                            ->inlineLabel()
                            ->label('Code Snippet')
                            ->visible(fn (?string $state): bool => ! is_null($state))
                            ->formatStateUsing(fn ($state) => new HtmlString('<pre class="border-gray-100 bg-gray-50 p-2">' . htmlspecialchars($state) . '</pre>')),
                        TextEntry::make('question.more_info_link')
                            ->inlineLabel()
                            ->label('More Information')
                            ->url(fn (?string $state): string => $state)
                            ->visible(fn (?string $state): bool => ! is_null($state)),
                    ]),
            ]);
    }
}