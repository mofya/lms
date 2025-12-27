<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionGenerationResource\Pages;
use App\Models\QuestionGeneration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuestionGenerationResource extends Resource
{
    protected static ?string $model = QuestionGeneration::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Generation History';

    protected static UnitEnum|string|null $navigationGroup = 'Quiz Management';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('provider')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'openai' => 'success',
                        'anthropic' => 'warning',
                        'gemini' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('prompt_params')
                    ->label('Topic')
                    ->formatStateUsing(fn ($state) => $state['topic'] ?? '-')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state['topic'] ?? null),

                TextColumn::make('prompt_params')
                    ->label('Difficulty')
                    ->formatStateUsing(fn ($state) => ucfirst($state['difficulty'] ?? '-')),

                TextColumn::make('questions_generated')
                    ->label('Questions')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Generated')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('rerun')
                    ->label('Re-run')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->url(fn (QuestionGeneration $record): string => route('filament.admin.pages.quiz-question-generator', [
                        'prefill' => base64_encode(json_encode($record->prompt_params)),
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionGenerations::route('/'),
        ];
    }
}

