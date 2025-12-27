<?php

namespace App\Filament\Student\Resources;

use BackedEnum;
use App\Filament\Student\Resources\DiscussionResource\Pages;
use App\Models\Discussion;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DiscussionResource extends Resource
{
    protected static ?string $model = Discussion::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Discussions';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show discussions for courses the student is enrolled in
                $query->whereHas('course.students', function ($q) {
                    $q->where('users.id', Auth::id());
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pinned')
                    ->boolean(),
                Tables\Columns\TextColumn::make('replies_count')
                    ->label('Replies')
                    ->badge(),
                Tables\Columns\TextColumn::make('last_reply_at')
                    ->label('Last Reply')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course_id')
                    ->label('Course')
                    ->relationship('course', 'title')
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Pinned'),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->defaultSort('last_reply_at', 'desc');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Discussion')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->relationship('course', 'title', fn($query) => $query->whereHas('students', fn($q) => $q->where('users.id', Auth::id())))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('content')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'codeBlock',
                            ])
                            ->columnSpanFull(),
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
            'index' => Pages\ListDiscussions::route('/'),
            'create' => Pages\CreateDiscussion::route('/create'),
            'view' => Pages\ViewDiscussion::route('/{record}'),
        ];
    }
}
