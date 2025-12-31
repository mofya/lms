<?php

namespace App\Filament\Student\Pages;

use App\Models\Course;
use App\Services\AiStudyAssistant;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class StudyAssistant extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected string $view = 'filament.student.pages.study-assistant';

    protected static ?string $navigationLabel = 'Study Assistant';

    protected static ?int $navigationSort = 40;

    public ?string $question = '';

    public ?int $course_id = null;

    public ?string $response = '';

    public bool $isLoading = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('course_id')
                    ->label('Course Context (Optional)')
                    ->options(
                        Course::whereHas('students', fn ($q) => $q->where('users.id', Auth::id()))
                            ->published()
                            ->pluck('title', 'id')
                    )
                    ->searchable()
                    ->placeholder('Select a course for context'),

                Textarea::make('question')
                    ->label('Your Question')
                    ->placeholder('Ask me anything about your courses...')
                    ->required()
                    ->rows(5),
            ])
            ->statePath('data');
    }

    public function askQuestion(): void
    {
        $data = $this->form->getState();
        $question = $data['question'] ?? '';
        $courseId = $data['course_id'] ?? null;

        if (empty($question)) {
            \Filament\Notifications\Notification::make()
                ->title('Please enter a question')
                ->warning()
                ->send();

            return;
        }

        $this->isLoading = true;
        $this->response = '';

        $course = $courseId ? Course::find($courseId) : null;
        $assistant = new AiStudyAssistant;

        try {
            $response = $assistant->askQuestion($question, $course);
            $this->response = is_string($response) ? $response : 'Received an unexpected response format.';
        } catch (\Exception $e) {
            $this->response = 'Error: '.$e->getMessage();
        }

        $this->isLoading = false;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('ask')
                ->label('Ask Question')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action('askQuestion')
                ->disabled(fn () => $this->isLoading),
        ];
    }
}
