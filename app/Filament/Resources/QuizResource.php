<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\QuizResource\Pages;
use App\Filament\Resources\QuizResource\RelationManagers;
use App\Models\Quiz;
use Filament\Forms;
use Filament\Schemas\Schema;
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
