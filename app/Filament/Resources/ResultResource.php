<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Models\Test;
use App\Filament\Resources\ResultResource\Pages;
use App\Filament\Resources\ResultResource\RelationManagers;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResultResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $label = 'Result';

    protected static UnitEnum|string|null $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->when(! auth()->user()->is_admin)
                    ->where('user_id', auth()->id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('quiz.title'),
                Tables\Columns\TextColumn::make('user.name')
                    ->visible(auth()->user()->is_admin),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date'),
                Tables\Columns\TextColumn::make('score')
                    ->label('Result')
                    ->counts('questions')
                    ->formatStateUsing(fn (Test $record) => $record->score . '/' . $record->questions_count),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('View')
                    ->url(fn (Test $record) => Pages\ViewResult::getUrl(['record' => $record])),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListResults::route('/'),
            'view' => Pages\ViewResult::route('/{record}'),
        ];
    }
}
