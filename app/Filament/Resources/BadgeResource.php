<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\BadgeResource\Pages;
use App\Models\Badge;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BadgeResource extends Resource
{
    protected static ?string $model = Badge::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $label = 'Badge';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 15;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Badge Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon (Heroicon name)')
                            ->default('heroicon-o-star')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('color')
                            ->options([
                                'success' => 'Green',
                                'info' => 'Blue',
                                'warning' => 'Yellow',
                                'danger' => 'Red',
                                'primary' => 'Primary',
                            ])
                            ->default('success'),
                    ]),
                
                Section::make('Trigger Settings')
                    ->schema([
                        Forms\Components\Select::make('trigger_type')
                            ->label('Trigger Type')
                            ->options([
                                'first_quiz' => 'First Quiz Completed',
                                'first_assignment' => 'First Assignment Submitted',
                                'course_completed' => 'Course Completed',
                                'perfect_score' => 'Perfect Quiz Score',
                                'streak' => 'Learning Streak',
                                'quizzes_completed' => 'Quizzes Completed',
                                'assignments_completed' => 'Assignments Completed',
                                'discussions_participated' => 'Discussions Participated',
                            ])
                            ->required()
                            ->reactive()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('trigger_value')
                            ->label('Trigger Value')
                            ->helperText('Required for streak, quizzes_completed, assignments_completed, discussions_participated')
                            ->numeric()
                            ->visible(fn (callable $get) => in_array($get('trigger_type'), ['streak', 'quizzes_completed', 'assignments_completed', 'discussions_participated'])),
                        Forms\Components\Checkbox::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('trigger_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state))),
                Tables\Columns\TextColumn::make('trigger_value')
                    ->label('Value')
                    ->default('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Earned By')
                    ->counts('users')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->options([
                        'first_quiz' => 'First Quiz',
                        'first_assignment' => 'First Assignment',
                        'course_completed' => 'Course Completed',
                        'perfect_score' => 'Perfect Score',
                        'streak' => 'Streak',
                        'quizzes_completed' => 'Quizzes Completed',
                        'assignments_completed' => 'Assignments Completed',
                        'discussions_participated' => 'Discussions Participated',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListBadges::route('/'),
            'create' => Pages\CreateBadge::route('/create'),
            'edit' => Pages\EditBadge::route('/{record}/edit'),
        ];
    }
}
