<?php

namespace App\Filament\Student\Resources\CourseResource\Pages;

use App\Filament\Student\Resources\CourseResource;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;
}
