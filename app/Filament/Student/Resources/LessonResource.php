<?php

namespace App\Filament\Student\Resources;

use BackedEnum;
use App\Models\Lesson;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-play-circle';

    protected static bool $shouldRegisterNavigation = false;

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}
