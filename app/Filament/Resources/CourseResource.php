<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\CourseResource\Pages\CreateCourse;
use App\Filament\Resources\CourseResource\Pages\EditCourse;
use App\Filament\Resources\CourseResource\Pages\ListCourses;
use App\Filament\Resources\LessonResource\Pages\CreateLesson;
use App\Filament\Resources\LessonResource\Pages\EditLesson;
use App\Filament\Resources\LessonResource\Pages\ListLessons;
use App\Models\Course;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Filament\Forms\Components\Select;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static UnitEnum|string|null $navigationGroup = 'Course Content';

    protected static ?int $navigationSort = 10;

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->title;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Course Details')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->nullable()
                            ->columnSpanFull(),
                        Select::make('lecturer_id')
                            ->relationship('lecturer', 'name')
                            ->required()
                            ->columnSpanFull(),
                        Checkbox::make('is_published')
                            ->label('Published'),
                    ]),

                Section::make('Featured Image')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('Featured Image')
                            ->collection('featured_image')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('Featured Image')
                    ->collection('featured_image'),
                TextColumn::make('title'),
                TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('Manage lessons')
                    ->color('success')
                    ->icon('heroicon-m-academic-cap')
                    ->url(fn (Course $record): string => self::getUrl('lessons.index', [
                        'parent' => $record->id,
                    ])),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
            'index'  => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'edit'   => EditCourse::route('/{record}/edit'),

            // Lessons
            'lessons.index'  => ListLessons::route('/{parent}/lessons'),
            'lessons.create' => CreateLesson::route('/{parent}/lessons/create'),
            'lessons.edit'   => EditLesson::route('/{parent}/lessons/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        $parameters['tenant'] ??= ($tenant ?? Filament::getTenant());

        $routeBaseName = static::getRouteBaseName(panel: $panel);
        $routeFullName = "{$routeBaseName}." . ($name ?? 'index');
        $route        = Route::getRoutes()->getByName($routeFullName);

        if ($route && str($route->uri())->contains('{parent}')) {
            $parameters['parent'] ??= (request()->route('parent') ?? request()->input('parent'));
        }

        return route($routeFullName, $parameters, $isAbsolute);
    }
}