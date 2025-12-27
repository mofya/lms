<?php

namespace App\Filament\Student\Resources\StudentResultResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Student\Resources\StudentResultResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentResult extends EditRecord
{
    protected static string $resource = StudentResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
