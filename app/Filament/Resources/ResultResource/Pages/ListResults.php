<?php

namespace App\Filament\Resources\ResultResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ResultResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListResults extends ListRecords
{
    protected static string $resource = ResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
