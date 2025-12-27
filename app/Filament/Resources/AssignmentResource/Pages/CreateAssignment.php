<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $assignment = static::getModel()::create($data);

        // Create rubric if provided
        if (isset($data['rubric'])) {
            $rubricData = $data['rubric'];
            $rubric = $assignment->rubric()->create([
                'type' => $rubricData['type'] ?? 'freeform',
                'freeform_text' => $rubricData['freeform_text'] ?? null,
            ]);

            // Create criteria if structured rubric
            if (($rubricData['type'] ?? 'freeform') === 'structured' && isset($rubricData['criteria'])) {
                foreach ($rubricData['criteria'] as $index => $criterion) {
                    $rubric->criteria()->create([
                        'name' => $criterion['name'],
                        'description' => $criterion['description'] ?? null,
                        'max_points' => $criterion['max_points'],
                        'position' => $index,
                    ]);
                }
            }
        }

        return $assignment;
    }
}
