<?php

namespace App\Filament\Resources\LessonResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CourseResource;
use App\Filament\Resources\LessonResource;
use App\Filament\Traits\HasParentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ListLessons extends ListRecords
{
    use HasParentResource;

    protected static string $parentResource = CourseResource::class;

    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(fn (): string => $this->getParentResource()::getUrl('lessons.create', [
                    'parent' => $this->parent,
                ])),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->pushRecordActions([
            EditAction::make()
                ->url(fn (Model $record): string => CourseResource::getUrl('lessons.edit', [
                    'record' => $record,
                    'parent' => $this->parent,
                ])),
            DeleteAction::make(),
        ]);
    }
}