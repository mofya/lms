<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionGeneration;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Services\LlmQuestionGenerator;
use BackedEnum;
use UnitEnum;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Pages\Page;

class QuizQuestionGenerator extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public array $formData = [];
    public ?int $courseId = null;
    public ?int $quizId = null;
    public ?string $topic = '';
    public ?int $numQuestions = 5;
    public ?string $difficulty = 'mixed';
    public array $questionTypes = [];
    public bool $includeCodeSnippets = false;
    public ?string $additionalInstructions = '';
    public ?string $provider = 'openai';
    public ?string $resultMessage = '';
    public int $step = 1;
    public array $draftQuestions = [];
    public array $baseParams = [];

    protected array $casts = [
        'draftQuestions' => 'array',
        'questionTypes' => 'array',
        'formData' => 'array',
        'baseParams' => 'array',
    ];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bolt';
    protected string $view = 'filament.pages.quiz-question-generator';
    protected static ?string $title = 'Generate Quiz Questions';

    protected static UnitEnum|string|null $navigationGroup = 'Quiz Management';

    protected static ?int $navigationSort = 30;

    public function mount(): void
    {
        $defaults = [
            'provider' => 'openai',
            'num_questions' => 5,
            'difficulty' => 'mixed',
            'questionTypes' => [
                ['type' => Question::TYPE_MULTIPLE_CHOICE, 'count' => 5],
            ],
        ];

        // Support prefilling from generation history re-run
        $prefill = request()->query('prefill');
        if ($prefill) {
            $decoded = json_decode(base64_decode($prefill), true);
            if (is_array($decoded)) {
                $defaults = array_merge($defaults, [
                    'course_id' => $decoded['course_id'] ?? null,
                    'quiz_id' => $decoded['quiz_id'] ?? null,
                    'topic' => $decoded['topic'] ?? '',
                    'num_questions' => $decoded['num_questions'] ?? 5,
                    'difficulty' => $decoded['difficulty'] ?? 'mixed',
                    'questionTypes' => $decoded['question_types'] ?? $defaults['questionTypes'],
                    'include_code_snippets' => $decoded['include_code_snippets'] ?? false,
                    'additional_instructions' => $decoded['additional_instructions'] ?? '',
                    'provider' => $decoded['provider'] ?? 'openai',
                ]);
            }
        }

        $this->form->fill($defaults);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Context')
                ->description('Optional: Provide course or quiz context to improve question generation')
                ->schema([
                    Select::make('course_id')
                        ->label('Course (optional)')
                        ->options(Course::pluck('title', 'id')->toArray())
                        ->searchable()
                        ->placeholder('Use course context in the prompt')
                        ->columnSpanFull(),

                    Select::make('quiz_id')
                        ->label('Assign to Quiz (optional)')
                        ->options(Quiz::pluck('title', 'id')->toArray())
                        ->searchable()
                        ->columnSpanFull(),
                ]),

            Section::make('Generation Settings')
                ->schema([
                    TextInput::make('topic')
                        ->label('Topic / Keywords')
                        ->placeholder('e.g., Dependency Injection, Laravel Service Container')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('num_questions')
                        ->label('Number of Questions')
                        ->numeric()
                        ->default(5)
                        ->minValue(1)
                        ->maxValue(50)
                        ->required()
                        ->columnSpanFull(),

                    Select::make('difficulty')
                        ->label('Difficulty')
                        ->options([
                            'easy' => 'Easy',
                            'medium' => 'Medium',
                            'hard' => 'Hard',
                            'mixed' => 'Mixed',
                        ])
                        ->default('mixed')
                        ->required()
                        ->columnSpanFull(),

                    Repeater::make('questionTypes')
                        ->label('Question Types & Counts')
                        ->schema([
                            Select::make('type')
                                ->options([
                                    Question::TYPE_MULTIPLE_CHOICE => 'Multiple choice',
                                    Question::TYPE_CHECKBOX => 'Checkbox (multiple correct)',
                                    Question::TYPE_SINGLE_ANSWER => 'Single answer (short text)',
                                ])
                                ->required(),
                            TextInput::make('count')
                                ->label('Count')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(50)
                                ->required(),
                        ])
                        ->minItems(1)
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Advanced Options')
                ->schema([
                    Toggle::make('include_code_snippets')
                        ->label('Include code snippets when relevant')
                        ->default(false)
                        ->columnSpanFull(),

                    Textarea::make('additional_instructions')
                        ->label('Additional instructions')
                        ->rows(3)
                        ->columnSpanFull(),

                    Select::make('provider')
                        ->label('LLM Provider')
                        ->options([
                            'openai' => 'OpenAI',
                            'anthropic' => 'Anthropic Claude',
                            'gemini' => 'Google Gemini',
                        ])
                        ->default('openai')
                        ->required()
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'formData';
    }

    public function submit(): void
    {
        $this->resultMessage = '';
        $state = $this->form->getState();

        $params = [
            'course_id' => $state['course_id'] ?? null,
            'quiz_id' => $state['quiz_id'] ?? null,
            'topic' => $state['topic'] ?? '',
            'num_questions' => $state['num_questions'] ?? 5,
            'difficulty' => $state['difficulty'] ?? 'mixed',
            'question_types' => $state['questionTypes'] ?? [],
            'include_code_snippets' => $state['include_code_snippets'] ?? false,
            'additional_instructions' => $state['additional_instructions'] ?? '',
            'provider' => $state['provider'] ?? 'openai',
        ];

        $this->baseParams = $params;
        $this->quizId = $params['quiz_id'];

        $service = new LlmQuestionGenerator();
        $generatedData = $service->generateQuestions($params);

        if (is_string($generatedData)) {
            $this->resultMessage = 'Error: ' . $generatedData;
            return;
        }

        if (! is_array($generatedData)) {
            $this->resultMessage = 'Error: Unexpected response format from LLM.';
            return;
        }

        $questions = $this->sanitizeQuestions($generatedData);

        if (empty($questions)) {
            $this->resultMessage = 'Error: No questions returned.';
            return;
        }

        $this->draftQuestions = $questions;
        $this->step = 2;
        $this->resultMessage = 'Draft questions generated. Review and edit before saving.';

        // Record generation history
        QuestionGeneration::create([
            'user_id' => auth()->id(),
            'provider' => $params['provider'],
            'prompt_params' => $params,
            'questions_generated' => count($questions),
        ]);
    }

    public function regenerateQuestion(int $index): void
    {
        if (! isset($this->draftQuestions[$index])) {
            return;
        }

        $current = $this->draftQuestions[$index];
        $params = $this->baseParams ?: [];
        $params['num_questions'] = 1;
        $params['question_types'] = [
            [
                'type' => $current['type'] ?? Question::TYPE_MULTIPLE_CHOICE,
                'count' => 1,
            ],
        ];

        $service = new LlmQuestionGenerator();
        $generated = $service->generateQuestions($params);

        if (! is_array($generated) || empty($generated)) {
            $this->resultMessage = 'Error: Could not regenerate this question.';
            return;
        }

        $replacement = $this->sanitizeQuestions([$generated[0]])[0] ?? null;

        if (! $replacement) {
            $this->resultMessage = 'Error: Regeneration returned an invalid question.';
            return;
        }

        $this->draftQuestions[$index] = $replacement;
        $this->resultMessage = 'Question regenerated.';
    }

    public function removeQuestion(int $index): void
    {
        if (! isset($this->draftQuestions[$index])) {
            return;
        }

        unset($this->draftQuestions[$index]);
        $this->draftQuestions = array_values($this->draftQuestions);
    }

    public function addOption(int $questionIndex): void
    {
        if (! isset($this->draftQuestions[$questionIndex])) {
            return;
        }

        $this->draftQuestions[$questionIndex]['options'][] = [
            'option' => '',
            'correct' => false,
        ];
    }

    public function removeOption(int $questionIndex, int $optionIndex): void
    {
        if (! isset($this->draftQuestions[$questionIndex]['options'][$optionIndex])) {
            return;
        }

        unset($this->draftQuestions[$questionIndex]['options'][$optionIndex]);
        $this->draftQuestions[$questionIndex]['options'] = array_values($this->draftQuestions[$questionIndex]['options']);
    }

    public function restart(): void
    {
        $this->step = 1;
        $this->draftQuestions = [];
        $this->resultMessage = null;
    }

    public function saveApproved(): void
    {
        if (empty($this->draftQuestions)) {
            $this->resultMessage = 'No draft questions to save.';
            return;
        }

        foreach ($this->draftQuestions as $questionData) {
            if (! isset($questionData['question_text'])) {
                continue;
            }

            $question = Question::create([
                'question_text' => $questionData['question_text'],
                'answer_explanation' => $questionData['answer_explanation'] ?? null,
                'more_info_link' => $questionData['more_info_link'] ?? null,
                'correct_answer' => $questionData['correct_answer'] ?? null,
                'type' => $questionData['type'] ?? Question::TYPE_MULTIPLE_CHOICE,
            ]);

            foreach ($questionData['options'] ?? [] as $optionData) {
                if (! isset($optionData['option'])) {
                    continue;
                }

                QuestionOption::create([
                    'question_id' => $question->id,
                    'option' => $optionData['option'],
                    'correct' => (bool) ($optionData['correct'] ?? false),
                ]);
            }

            if ($this->quizId) {
                $question->quizzes()->attach($this->quizId);
            }
        }

        $this->resultMessage = 'Questions saved successfully.';
        $this->step = 1;
        $this->draftQuestions = [];
    }

    protected function sanitizeQuestions(array $data): array
    {
        $clean = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['question_text'])) {
                continue;
            }

            $clean[] = [
                'question_text' => $item['question_text'],
                'answer_explanation' => $item['answer_explanation'] ?? null,
                'more_info_link' => $item['more_info_link'] ?? null,
                'type' => $item['type'] ?? Question::TYPE_MULTIPLE_CHOICE,
                'correct_answer' => $item['correct_answer'] ?? null,
                'options' => array_values(array_filter($item['options'] ?? [], fn ($opt) => isset($opt['option']))),
            ];
        }

        return array_slice($clean, 0, 50);
    }
}