<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Models\Lesson;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->columnSpanFull()
                    ->required(),
                Select::make('type')
                    ->options([
                        Lesson::TYPE_TEXT => 'Text',
                        Lesson::TYPE_VIDEO => 'Video (YouTube)',
                    ])
                    ->default(Lesson::TYPE_TEXT)
                    ->required(),
                RichEditor::make('lesson_text')
                    ->label('Lesson body')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === Lesson::TYPE_TEXT),
                TextInput::make('video_url')
                    ->label('YouTube URL')
                    ->url()
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === Lesson::TYPE_VIDEO),
                TextInput::make('duration_seconds')
                    ->label('Duration (seconds)')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Shown to students; optional')
                    ->columnSpanFull(),
                Checkbox::make('is_published'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position'),
                TextColumn::make('title'),
            ])
            ->recordActions([
                // ...
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}