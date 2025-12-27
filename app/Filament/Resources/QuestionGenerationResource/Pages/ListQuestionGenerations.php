<?php

namespace App\Filament\Resources\QuestionGenerationResource\Pages;

use App\Filament\Resources\QuestionGenerationResource;
use Filament\Resources\Pages\ListRecords;

class ListQuestionGenerations extends ListRecords
{
    protected static string $resource = QuestionGenerationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

