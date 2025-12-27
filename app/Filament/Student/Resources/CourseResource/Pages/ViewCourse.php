<?php

namespace App\Filament\Student\Resources\CourseResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use App\Filament\Student\Resources\CourseResource;
use App\Filament\Student\Widgets\QuizWidget;
use App\Infolists\Components\ListLessons;
use App\Infolists\Components\ListQuizzes;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCourse extends ViewRecord
{
    protected static string $resource = CourseResource::class;

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
                        TextEntry::make('description')
                            ->html()
                            ->extraAttributes(['class' => 'text-base']),
                    ])
                    ->columnSpan(2),
                Grid::make()
                    ->columns(1)
                    ->schema([
                        ListLessons::make('Lessons')
                            ->course($this->getRecord()),
                    ])
                    ->columnSpan(1),
                Grid::make()
                    ->columns(1)
                    ->schema([
                        ListQuizzes::make('Quizzes')
                            ->course($this->getRecord()),
                    ])
                    ->columnSpan(1)
            ])
            ->columns(3);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            QuizWidget::class,
        ];
    }
}
