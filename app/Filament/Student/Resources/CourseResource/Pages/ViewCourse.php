<?php

namespace App\Filament\Student\Resources\CourseResource\Pages;

use App\Filament\Student\Resources\CourseResource;
use App\Infolists\Components\ListLessons;
use App\Infolists\Components\ListQuizzes;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->title;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getInfolistContentComponent(),
                Grid::make()
                    ->columns(2)
                    ->schema([
                        ListLessons::make('Lessons')
                            ->course($this->getRecord()),
                        ListQuizzes::make('Quizzes')
                            ->course($this->getRecord()),
                    ]),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [

        ];
    }
}
