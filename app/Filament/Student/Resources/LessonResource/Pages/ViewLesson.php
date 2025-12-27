<?php

namespace App\Filament\Student\Resources\LessonResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use App\Filament\Student\Resources\CourseResource;
use App\Filament\Student\Resources\LessonResource;
use App\Filament\Traits\HasParentResource;
use App\Infolists\Components\CompleteButton;
use App\Infolists\Components\CourseProgress;
use App\Infolists\Components\LessonContent;
use App\Infolists\Components\LessonPaginator;
use App\Infolists\Components\ListLessons;
use App\Infolists\Components\ListQuizzes;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewLesson extends ViewRecord
{
    use HasParentResource;

    protected static string $parentResource = CourseResource::class;

    protected static string $resource = LessonResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->title;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make()
                    ->columns(1)
                    ->schema([
                        CompleteButton::make(),
                        LessonContent::make(),
                        LessonPaginator::make()
                            ->currentLesson($this->getRecord()),
                    ])
                    ->columnSpan(2),
                Grid::make()
                    ->columns(1)
                    ->schema([
                        CourseProgress::make()
                            ->course($this->getRecord()->course),
                        ListLessons::make('Lessons')
                            ->course($this->getRecord()->course)
                            ->activeLesson($this->getRecord()),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public function toggleCompleted(): void
    {
        $lesson = $this->getRecord();

        $lesson->isCompleted()
            ? $lesson->markAsUncompleted()
            : $lesson->markAsCompleted();
    }

    public function markAsCompletedAndGoToNext()
    {
        $lesson = $this->getRecord();
        $lesson->markAsCompleted();

        return redirect()->to(static::getParentResource()::getUrl('lessons.view', [
            $lesson->course,
            $lesson->getNext(),
        ]));
    }
}
