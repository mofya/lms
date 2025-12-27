<?php

namespace App\Filament\Student\Resources;

use BackedEnum;
use App\Filament\Student\Resources\StudentResultResource\Pages;
use App\Filament\Student\Resources\StudentResultResource\RelationManagers;
use App\Models\StudentResult;
use App\Models\Test;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class StudentResultResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $label = 'My Results';

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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('user_id', Auth::id()); // Only fetch logged-in student's results
            })
            ->columns([
                Tables\Columns\TextColumn::make('quiz.title')
                    ->label('Quiz'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('result')
                    ->label('Score')
                    ->counts('questions')
                    ->formatStateUsing(fn (Test $record) => $record->result . '/' . $record->questions_count),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->url(fn (Test $record) => Pages\ViewStudentResult::getUrl(['record' => $record])),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListStudentResults::route('/'),
            'view' => Pages\ViewStudentResult::route('/{record}'),
        ];
    }
}
