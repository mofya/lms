<?php

namespace App\Filament\Resources\ResultResource\Pages;

use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Fieldset;
use App\Models\Test;
use Filament\Actions;
use Filament\Infolists;
use App\Models\TestAnswer;
use App\Models\QuestionOption;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\FontWeight;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ResultResource;

class ViewResult extends ViewRecord
{
    protected static string $resource = ResultResource::class;

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
                        TextEntry::make('question.questionOptions')
                            ->hiddenLabel()
                            ->html()
                            ->formatStateUsing(function (TestAnswer $answer) {
                                $question = $answer->question;
                                $options = $question->questionOptions;

                                // Handle Checkbox (Multiple Answers)
                                if ($question->type === 'checkbox') {
                                    $selectedOptions = $answer->option_id ? explode(',', $answer->option_id) : [];
                                    $correctOptions = $options->where('correct', true)->pluck('option')->toArray();

                                    return new HtmlString('<strong>Your Answers:</strong> ' . implode(', ', $options->whereIn('id', $selectedOptions)->pluck('option')->toArray()) .
                                        '<br><strong>Correct Answers:</strong> ' . implode(', ', $correctOptions));
                                }

                                // Handle Single Choice (Radio)
                                if ($question->type === 'multiple_choice') {
                                    $selectedOption = $options->firstWhere('id', $answer->option_id);
                                    return new HtmlString($selectedOption ? "<strong>Your Answer:</strong> {$selectedOption->option}" : "<strong>Your Answer:</strong> None") .
                                        ($selectedOption && $selectedOption->correct ? ' ✅' : ' ❌');
                                }

                                // Handle Single Answer (Text Input)
                                if ($question->type === 'single_answer') {
                                    $correctAnswer = $question->correct_answer ?? 'Not Provided';
                                    return new HtmlString("<strong>Your Answer:</strong> {$answer->text_answer} <br><strong>Correct Answer:</strong> {$correctAnswer}");
                                }

                                return 'Unknown Question Type';
                            }),
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
