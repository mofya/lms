<?php

namespace App\Filament\Student\Resources;

use App\Filament\Resources\QuizResource\Pages\ListQuizzes;
use App\Filament\Student\Resources\CourseResource\Pages\ListCourses;
use App\Filament\Student\Resources\CourseResource\Pages\ViewCourse;
use App\Filament\Student\Resources\LessonResource\Pages\ViewLesson;
use App\Models\Course;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->title;
    }

    public static function getNavigationLabel(): string
    {
        return 'All Courses';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                //
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Infolists\Components\TextEntry::make('description_text')
                    ->label('')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        SpatieMediaLibraryImageColumn::make('Featured Image')
                            ->collection('featured_image')
                            ->extraImgAttributes(['class' => 'w-full rounded'])
                            ->height('auto'),
                        TextColumn::make('title')
                            ->weight(FontWeight::SemiBold)
                            ->extraAttributes(['class' => 'text-lg']),
                        TextColumn::make('description')
                            ->html(),
                    ]),
            ])
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->paginationPageOptions([9, 18, 27])
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->published());
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
            'index' => ListCourses::route('/'),
            'view' => ViewCourse::route('/{record}'),
            'lessons.view' => ViewLesson::route('/{parent}/lessons/{record}'),
            'quizzes.index' => ListQuizzes::route('/{parent}/quizzes'),

        ];
    }
}
