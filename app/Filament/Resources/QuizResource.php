<?php

namespace App\Filament\Resources;

use App\Enums\NavigatorPosition;
use App\Filament\Resources\QuizResource\Pages;
use App\Models\Quiz;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static UnitEnum|string|null $navigationGroup = 'Quiz Management';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\Checkbox::make('is_published')
                            ->label('Published'),
                    ]),

                Section::make('Course Assignment')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->label('End Time')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Duration Settings')
                    ->description('Choose either duration per question OR total duration. Setting one will clear the other.')
                    ->schema([
                        Forms\Components\TextInput::make('duration_per_question')
                            ->label('Duration Per Question (seconds)')
                            ->numeric()
                            ->nullable()
                            ->columnSpanFull()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $state ? $set('total_duration', null) : null),

                        Forms\Components\TextInput::make('total_duration')
                            ->label('Total Quiz Duration (seconds)')
                            ->numeric()
                            ->nullable()
                            ->columnSpanFull()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $state ? $set('duration_per_question', null) : null),
                    ]),

                Section::make('Questions')
                    ->schema([
                        Forms\Components\Select::make('questions')
                            ->multiple()
                            ->columnSpanFull()
                            ->relationship('questions', 'question_text')
                            ->createOptionForm(QuestionResource::questionForm()),
                    ]),

                Section::make('Attempts')
                    ->schema([
                        Forms\Components\TextInput::make('attempts_allowed')
                            ->label('Allowed Attempts')
                            ->numeric()
                            ->default(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Display Settings')
                    ->description('Configure how the quiz is presented to students.')
                    ->schema([
                        Forms\Components\Toggle::make('show_one_question_at_a_time')
                            ->label('One Question Per Page')
                            ->helperText('Show questions one at a time instead of all at once.')
                            ->default(true),

                        Forms\Components\Select::make('navigator_position')
                            ->label('Question Navigator Position')
                            ->options(NavigatorPosition::options())
                            ->default(NavigatorPosition::Bottom->value)
                            ->helperText('Where to display the question navigation grid.')
                            ->visible(fn (Get $get): bool => $get('show_one_question_at_a_time')),

                        Forms\Components\Toggle::make('allow_question_navigation')
                            ->label('Allow Question Navigation')
                            ->helperText('Allow students to navigate back to previous questions.')
                            ->default(true)
                            ->visible(fn (Get $get): bool => $get('show_one_question_at_a_time')),

                        Forms\Components\Toggle::make('auto_advance_on_answer')
                            ->label('Auto-Advance After Answer')
                            ->helperText('Automatically move to next question after selecting an answer.')
                            ->default(false)
                            ->visible(fn (Get $get): bool => $get('show_one_question_at_a_time')),

                        Forms\Components\Toggle::make('show_progress_bar')
                            ->label('Show Progress Bar')
                            ->helperText('Display a progress bar showing quiz completion.')
                            ->default(true),

                        Forms\Components\Toggle::make('shuffle_questions')
                            ->label('Shuffle Questions')
                            ->helperText('Randomize the order of questions for each attempt.')
                            ->default(true),

                        Forms\Components\Toggle::make('shuffle_options')
                            ->label('Shuffle Answer Options')
                            ->helperText('Randomize the order of answer options for each question.')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('attempts_allowed'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions'),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
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
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }
}
