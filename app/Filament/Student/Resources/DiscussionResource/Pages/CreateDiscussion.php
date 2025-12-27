<?php

namespace App\Filament\Student\Resources\DiscussionResource\Pages;

use App\Filament\Student\Resources\DiscussionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscussion extends CreateRecord
{
    protected static string $resource = DiscussionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        return $data;
    }
}
