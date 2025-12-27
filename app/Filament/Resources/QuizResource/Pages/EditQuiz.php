<?php

namespace App\Filament\Resources\QuizResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\QuizResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
