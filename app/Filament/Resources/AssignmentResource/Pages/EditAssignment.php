<?php

namespace App\Filament\Resources\AssignmentResource\Pages;

use App\Filament\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle rubric update
        if (isset($data['rubric'])) {
            $rubricData = $data['rubric'];
            $assignment = $this->record;

            // Update or create rubric
            $rubric = $assignment->rubric;
            if (!$rubric) {
                $rubric = $assignment->rubric()->create([
                    'type' => $rubricData['type'] ?? 'freeform',
                    'freeform_text' => $rubricData['freeform_text'] ?? null,
                ]);
            } else {
                $rubric->update([
                    'type' => $rubricData['type'] ?? 'freeform',
                    'freeform_text' => $rubricData['freeform_text'] ?? null,
                ]);
            }

            // Handle structured criteria
            if (($rubricData['type'] ?? 'freeform') === 'structured') {
                // Delete existing criteria
                $rubric->criteria()->delete();

                // Create new criteria
                if (isset($rubricData['criteria'])) {
                    foreach ($rubricData['criteria'] as $index => $criterion) {
                        $rubric->criteria()->create([
                            'name' => $criterion['name'],
                            'description' => $criterion['description'] ?? null,
                            'max_points' => $criterion['max_points'],
                            'position' => $index,
                        ]);
                    }
                }
            } else {
                // Delete criteria if switching to freeform
                $rubric->criteria()->delete();
            }

            // Remove rubric from data to avoid trying to save it directly
            unset($data['rubric']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $assignment = $this->record;
        $rubric = $assignment->rubric;

        if ($rubric) {
            $data['rubric'] = [
                'type' => $rubric->type,
                'freeform_text' => $rubric->freeform_text,
                'criteria' => $rubric->criteria->map(function ($criterion) {
                    return [
                        'name' => $criterion->name,
                        'description' => $criterion->description,
                        'max_points' => $criterion->max_points,
                    ];
                })->toArray(),
            ];
        }

        return $data;
    }
}
