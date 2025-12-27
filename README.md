## Quiz Application

In this example, we demonstrate how to create a quiz application. Only admin users can create quizzes with questions. Regular users can take published quizzes, see their own results, and view the leaderboard.

The repository contains the complete Laravel + Filament project to demonstrate the functionality, including migrations/seeds for the demo data.

The Filament project is in the `app/Filament` folder.

Feel free to pick the parts that you actually need in your projects.

---

## How to Install

-   Clone the repository with `git clone`
-   Copy the `.env.example` file to `.env` and edit database credentials there
-   Run `composer install`
-   Run `php artisan key:generate`
-   Run `php artisan migrate --seed` (it has some seeded data for your testing)
-   That's it: launch the URL `/admin` and log in with credentials `admin@admin.com` and `password` for admin user or register with a new user.

---

## Screenshot

![](https://laraveldaily.com/uploads/2025/01/filamentexamples-quiz-1.png)

![](https://laraveldaily.com/uploads/2025/01/filamentexamples-quiz-2.png)

![](https://laraveldaily.com/uploads/2025/01/filamentexamples-quiz-3.png)

![](https://laraveldaily.com/uploads/2025/01/filamentexamples-quiz-4.png)

![](https://laraveldaily.com/uploads/2025/01/filamentexamples-quiz-5.png)

---

## How it Works

We have two Filament resources for managing quizzes and questions. Only admin users can access these two resources. Permissions are done via policies.

**app/Policies/QuizPolicy.php**:
```php
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuizPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $quiz->is_published || $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->is_admin;
    }
}
```

## LLM configuration for quiz generation

- OpenAI: set `OPENAI_API_KEY` (optional `OPENAI_MODEL`, default `gpt-4o-mini`)
- Anthropic: set `ANTHROPIC_API_KEY` (optional `ANTHROPIC_MODEL`, default `claude-3-5-sonnet-latest`)
- Gemini: set `GEMINI_API_KEY` (optional `GEMINI_MODEL`, default `gemini-1.5-flash`)

Choose the provider on the “Generate Quiz Questions” admin page. If a key is missing for the selected provider, a friendly error message is shown instead of failing silently.

**app/Policies/QuestionPolicy.php**:
```php
use App\Models\User;
use App\Models\Question;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Question $question): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Question $question): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->is_admin;
    }
}
```

The question form is extracted to a separate method for reuse in the quiz resource. When creating a new question, you can also select a quiz in the `QuestionResource` on the edit page.

**app/Filament/Resources/QuestionResource.php**:
```php
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(array_merge(
                static::questionForm(),
                [
                    Forms\Components\Select::make('quiz_id')
                        ->multiple()
                        ->columnSpanFull()
                        ->visibleOn('edit')
                        ->relationship('quizzes', 'title')
                ])
            );
    }

    // ...

    public static function questionForm(): array
    {
        return [
            Forms\Components\Textarea::make('question_text')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Repeater::make('questionOptions')
                ->required()
                ->relationship()
                ->columnSpanFull()
                ->schema([
                    Forms\Components\TextInput::make('option')
                        ->required()
                        ->hiddenLabel(),
                    Forms\Components\Checkbox::make('correct'),
                ])->columns(),
            Forms\Components\Textarea::make('code_snippet')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('answer_explanation')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('more_info_link')
                ->columnSpanFull(),
        ];
    }
}
```

You can also create questions while creating a quiz in the `Quiz` form. This is where we reuse the question form.

**app/Filament/QuizResource.php**:
```php
use App\Models\Quiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('questions')
                    ->multiple()
                    ->columnSpanFull()
                    ->relationship('questions', 'question_text')
                    ->createOptionForm(QuestionResource::questionForm()),
                Forms\Components\Checkbox::make('is_published')
                    ->label('Published'),
            ]);
    }

    // ...
}
```

Taking the quiz is a custom Filament page that uses Livewire features. From the Filament part, we only overwrite the route path.

**app/Filament/Pages/TakeQuiz.php**:
```php
use App\Models\Test;
use App\Models\Quiz;
use Filament\Pages\Page;
use App\Models\Question;
use App\Models\TestAnswer;
use App\Models\QuestionOption;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ResultResource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Relations\HasMany as Builder;

class TakeQuiz extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.take-quiz';

    #[Locked]
    public Model | int | string | null $record;

    public Collection $questions;

    public Question $currentQuestion;

    public int $currentQuestionIndex = 0;

    public array $questionsAnswers = [];

    public int $startTimeSeconds = 0;

    public function mount(int | string $record): void
    {
        $this->record = Quiz::findOrFail($record);

        abort_if(! $this->record->is_published, 404);

        $this->questions = Question::query()
            ->inRandomOrder()
            ->whereRelation('quizzes', 'id', $this->record->id)
            ->with(['questionOptions' => fn (Builder $query) => $query->inRandomOrder()])
            ->get();

        $this->currentQuestion = $this->questions[$this->currentQuestionIndex];

        for($i = 0; $i < $this->questionsCount; $i++) {
            $this->questionsAnswers[$i] = [];
        }

        $this->startTimeSeconds = now()->timestamp;
    }

    public static function getRoutePath(): string
    {
        return '/' . static::getSlug() . '/{record}';
    }

    public function changeQuestion(): void
    {
        $this->currentQuestionIndex++;

        if ($this->currentQuestionIndex >= $this->questionsCount) {
            $this->submit();
        }

        $this->currentQuestion = $this->questions[$this->currentQuestionIndex];
    }

    public function submit(): void
    {
        $result = 0;

        $test = Test::create([
            'user_id'    => auth()->id(),
            'quiz_id'    => $this->record->id,
            'result'     => 0,
            'ip_address' => request()->ip(),
            'time_spent' => now()->timestamp - $this->startTimeSeconds,
        ]);

        foreach ($this->questionsAnswers as $key => $option) {
            info($option);
            $status = 0;

            if (! empty($option) && QuestionOption::find($option)->correct) {
                $status = 1;
                $result++;
            }

            TestAnswer::create([
                'user_id'     => auth()->id(),
                'test_id'     => $test->id,
                'question_id' => $this->questions[$key]->id,
                'option_id'   => $option ?? null,
                'correct'     => $status,
            ]);
        }

        $test->update([
            'result' => $result,
        ]);

        $this->redirectIntended(ResultResource::getUrl('view', ['record' => $test]));
    }

    public function getHeading(): string|Htmlable
    {
        return $this->record->title;
    }

    #[Computed]
    public function questionsCount(): int
    {
        return $this->questions->count();
    }
}
```

We used the Filament section, radio select, and button Blade components in the take quiz View file.

**resources/views/filament/pages/take-quiz.blade.php**:
```blade
<x-filament-panels::page>
    <div
        x-data="{ secondsLeft: {{ config('quiz.secondsPerQuestion') }} }"
        x-init="setInterval(() => { if (secondsLeft > 1) { secondsLeft--; } else { secondsLeft = {{ config('quiz.secondsPerQuestion') }}; $wire.changeQuestion(); } }, 1000);">

        <div class="mb-2">
            Time left for this question: <span x-text="secondsLeft" class="font-bold"></span> sec.
        </div>

        <x-filament::section class="mt-6">
            <span class="font-bold">Question {{ $currentQuestionIndex + 1 }} of {{ $this->questionsCount }}:</span>
            <h2 class="mb-4 text-2xl">{{ $currentQuestion->question_text }}</h2>

            @if ($currentQuestion->code_snippet)
                <pre class="mb-4 border-2 border-gray-100 bg-gray-50 p-2">{{ $currentQuestion->code_snippet }}</pre>
            @endif

            @foreach($currentQuestion->questionOptions as $option)
                <div wire:key="option.{{ $option->id }}">
                    <label for="option.{{ $option->id }}">
                        <x-filament::input.radio
                            id="option.{{ $option->id }}"
                            value="{{ $option->id }}"
                            name="questionsAnswers.{{ $currentQuestionIndex }}"
                            wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                        />

                        <span>
                            {{ $option->option }}
                        </span>
                    </label>
                </div>
            @endforeach
        </x-filament::section>

        <div class="mt-6">
            @if ($currentQuestionIndex < $this->questionsCount - 1)
                <x-filament::button type="submit" x-on:click="secondsLeft = {{ config('quiz.secondsPerQuestion') }}; $wire.changeQuestion();">
                    Next question
                </x-filament::button>
            @else
                <x-filament::button type="submit" wire:click="submit">
                    Submit
                </x-filament::button>
            @endif
        </div>
    </div>
</x-filament-panels::page>
```

How much time user have to answer the question is set in the config file.

**config/quiz.php**:
```php
return [
    'secondsPerQuestion' => 60,
];
```

The `ResultResource` only has list and view pages. In the list page the query is modified to show results only for the user if he isn't admin. The `ResultResource` model is changed to a `Test`, but the label still left `Result`.

**app/Filament/Resources/ResultResource.php**:
```php
use App\Models\Test;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResultResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $label = 'Result';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->when(! auth()->user()->is_admin)
                    ->where('user_id', auth()->id());
            })
            ->columns([
                Tables\Columns\TextColumn::make('quiz.title'),
                Tables\Columns\TextColumn::make('user.name')
                    ->visible(auth()->user()->is_admin),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Result')
                    ->counts('questions')
                    ->formatStateUsing(fn (Test $record) => $record->result . '/' . $record->questions_count),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    // ...
}
```

For the result view, an infolist is used, but everything instead of added to a resource instead is added to a View class to be able to use methods like `getRecord()`.

In the `mount()` method, we must also eager load relationships to avoid the n+1 problem.

**app/Filament/Resources/ResultResource/Pages/ViewResult.php**:
```php
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ResultResource;

class ViewResult extends ViewRecord
{
    protected static string $resource = ResultResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load('testAnswers.question.questionOptions'); // [tl! ~~]

        $this->authorizeAccess();

        if (! $this->hasInfolist()) {
            $this->fillForm();
        }
    }

    // ...
}
```

In the infolist, we use a `RepeatableEntry` to show questions. Some fields' states must be heavily formatted before showing.

**app/Filament/Resources/ResultResource/Pages/ViewResult.php**:
```php
use App\Models\Test;
use Filament\Infolists;
use App\Models\TestAnswer;
use App\Models\QuestionOption;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\FontWeight;

class ViewResult extends ViewRecord
{
    // ...

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->columns(1)
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->inlineLabel()
                            ->label('Date'),
                        Infolists\Components\TextEntry::make('result')
                            ->inlineLabel()
                            ->formatStateUsing(function (Test $record) {
                                return $record->result . '/' . $record->questions->count() . ' (time: ' . intval($record->time_spent / 60) . ':' . gmdate('s', $record->time_spent) . ' minutes)';
                            }),
                    ]),

                Infolists\Components\RepeatableEntry::make('testAnswers')
                    ->label('Questions')
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\TextEntry::make('question.question_text')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->weight(FontWeight::Bold),
                        Infolists\Components\RepeatableEntry::make('question.questionOptions')
                            ->inlineLabel()
                            ->contained(false)
                            ->schema([
                                Infolists\Components\TextEntry::make('option')
                                    ->html()
                                    ->hiddenLabel()
                                    ->weight(fn (QuestionOption $record) => $record->correct ? FontWeight::Bold : null)
                                    ->formatStateUsing(function (QuestionOption $record) {
                                        $answer = static::getRecord()->testAnswers->firstWhere(function (TestAnswer $value) use ($record) {
                                            return $value->question_id === $record->question_id;
                                        });

                                        return $record->option . ' ' .
                                            ($record->correct ? new HtmlString('<span class="italic">(correct answer)</span>') : null) . ' ' .
                                            ($answer->option_id == $record->id ? new HtmlString('<span class="italic">(your answer)</span>') : null);
                                    }),
                            ]),
                        Infolists\Components\TextEntry::make('question.code_snippet')
                            ->inlineLabel()
                            ->label('Code Snippet')
                            ->visible(fn (?string $state): bool => ! is_null($state))
                            ->formatStateUsing(fn ($state) => new HtmlString('<pre class="border-gray-100 bg-gray-50 p-2">' . htmlspecialchars($state) . '</pre>')),
                        Infolists\Components\TextEntry::make('question.more_info_link')
                            ->inlineLabel()
                            ->label('More Information')
                            ->url(fn (?string $state): string => $state)
                            ->visible(fn (?string $state): bool => ! is_null($state)),
                    ]),
            ]);
    }
}
```
