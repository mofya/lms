<?php

namespace App\Filament\Student\Resources\StudentResultResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Student\Resources\StudentResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentResults extends ListRecords
{
    protected static string $resource = StudentResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}