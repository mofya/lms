<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationLabel = 'Question Bank';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static UnitEnum|string|null $navigationGroup = 'Quiz Management';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema{
        return $schema
            ->schema(static::questionForm());
    }
    public static function questionForm(): array
    {
        return [
            Section::make('Question Type')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'multiple_choice' => 'Multiple Choice (Single Answer)',
                            'checkbox' => 'Checkbox (Multiple Answers)',
                            'single_answer' => 'Single Answer (Fill-in-the-Blank)',
                        ])
                        ->required()
                        ->reactive()
                        ->columnSpanFull(),
                ]),

            Section::make('Question Content')
                ->schema([
                    Forms\Components\Textarea::make('question_text')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('code_snippet')
                        ->label('Code Snippet')
                        ->columnSpanFull(),
                ]),

            Section::make('Answer Options')
                ->schema([
                    // For multiple choice or checkbox questions, include the repeater field
                    Fieldset::make('Options')
                        ->schema(self::multipleChoiceForm())
                        ->visible(fn (callable $get) => in_array($get('type'), ['multiple_choice', 'checkbox'])),

                    // For single answer questions, include a simple answer input
                    Fieldset::make('Answer')
                        ->schema(self::singleAnswerForm())
                        ->visible(fn (callable $get) => $get('type') === 'single_answer'),
                ]),

            Section::make('Additional Information')
                ->schema([
                    Forms\Components\Textarea::make('answer_explanation')
                        ->label('Answer Explanation')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('more_info_link')
                        ->label('More Info Link')
                        ->url()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('quiz_id')
                        ->label('Assign to Quizzes')
                        ->multiple()
                        ->columnSpanFull()
                        ->visibleOn('edit')
                        ->relationship('quizzes', 'title'),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question_text')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }

    public static function multipleChoiceForm(): array
    {
        return [
            Forms\Components\Repeater::make('questionOptions')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('option')
                        ->required()
                        ->hiddenLabel(),
                    Forms\Components\Checkbox::make('correct')
                        ->label('Mark as correct'),
                ])
                ->columnSpanFull()
                ->required(),
        ];
    }

    public static function singleAnswerForm(): array
    {
        return [
            Forms\Components\Textarea::make('correct_answer')
                ->label('Answer')
                ->columnSpanFull()
                ->required(),
        ];
    }


//    public static function questionForm(): array
//    {
//        return [
//            Forms\Components\Textarea::make('question_text')
//                ->required()
//                ->columnSpanFull(),
//            Forms\Components\Repeater::make('questionOptions')
//                ->required()
//                ->relationship()
//                ->columnSpanFull()
//                ->schema([
//                    Forms\Components\TextInput::make('option')
//                        ->required()
//                        ->hiddenLabel(),
//                    Forms\Components\Checkbox::make('correct'),
//                ])->columns(),
//            Forms\Components\Textarea::make('code_snippet')
//                ->columnSpanFull(),
//            Forms\Components\Textarea::make('answer_explanation')
//                ->columnSpanFull(),
//            Forms\Components\TextInput::make('more_info_link')
//                ->columnSpanFull(),
//        ];
//    }
}
