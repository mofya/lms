<?php

namespace App\Filament\Student\Resources\MyCoursesResource\Pages;

use App\Filament\Student\Resources\MyCoursesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyCourses extends ListRecords
{
    protected static string $resource = MyCoursesResource::class;

    public function getTitle(): string
    {
        return 'My Courses';
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
