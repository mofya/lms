# Best Practices from Laravel-Quizzes-Demo

This document summarizes the key patterns and features from the quiz demo application that we want to adopt into our LMS.

---

## 📦 Technology Stack

| Component | Demo App | Our LMS |
|-----------|----------|---------|
| Framework | Laravel 12 | Laravel 12 ✅ |
| Admin Panel | Filament v4 | Filament v4 ✅ |
| Frontend | **Livewire Flux** (UI kit) | Livewire + Blade |
| Styling | Tailwind v4 + Flux CSS | Tailwind v4 ✅ |
| Auth | Laravel Fortify | Filament Auth |

### Key Package: Livewire Flux
The demo uses `livewire/flux` - a premium UI component library that provides:
- `<flux:button>`, `<flux:badge>`, `<flux:heading>`, `<flux:text>`
- `<flux:sidebar>`, `<flux:navlist>`, `<flux:dropdown>`
- Dark mode support built-in
- Consistent styling across components

---

## 🎯 Features to Adopt

### 1. One-Question-Per-Page Quiz Interface

**Location:** `app/Livewire/Quizzes/TakeQuiz.php` + `resources/views/livewire/quizzes/take-quiz.blade.php`

**Key Features:**
- Single question displayed at a time with `currentQuestionIndex` tracking
- **Question Navigator Grid** at the bottom (5x8 grid of numbered buttons)
- Color-coded question status:
  - **Blue** = Current question
  - **Green** = Answered
  - **Gray** = Unanswered
- Auto-save answers on selection (`wire:model.live.debounce.500ms`)
- Auto-advance to next question after answering
- "Saving..." indicator while saving

**Implementation Pattern:**
```php
public int $currentQuestionIndex = 0;
public array $answers = [];

public function goToQuestion(int $index): void
{
    if ($index >= 0 && $index < $this->questions->count()) {
        $this->currentQuestionIndex = $index;
    }
}

public function updatedAnswers($value, $questionId): void
{
    $this->quizService->saveAnswer($this->attempt, (int) $questionId, (int) $value);
    $this->nextQuestion();
}
```

---

### 2. Quiz Timer with Auto-Submit

**Features:**
- Alpine.js countdown timer displayed in header
- Auto-submit when time expires via `@timer-expired` Livewire event
- Visual warning when < 60 seconds remain (red badge)
- Time displayed as `mm:ss` format

**Implementation:**
```html
<div x-data="{
    remainingSeconds: @entangle('remainingSeconds'),
    countdown() {
        if (this.remainingSeconds !== null && this.remainingSeconds > 0) {
            this.remainingSeconds--;
        }
        if (this.remainingSeconds === 0) {
            $wire.dispatch('timer-expired');
        }
    }
}"
x-init="if (remainingSeconds !== null) { setInterval(() => countdown(), 1000) }">
```

---

### 3. Progress Bar Component

**Features:**
- Shows answered/total questions count
- Visual progress bar with percentage fill
- Positioned between header and question

**Styling:**
```html
<div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
    <div class="h-full bg-blue-600 transition-all duration-300" 
         style="width: {{ $progressPercentage }}%"></div>
</div>
```

---

### 4. Quiz Results Page

**Location:** `resources/views/livewire/quizzes/quiz-results.blade.php`

**Features:**
- Large score display with pass/fail color (green ≥70%, red <70%)
- Correct/Wrong count with icons
- Time taken badge
- **Question Breakdown** with:
  - Correct/incorrect indicator circle
  - User's answer highlighted
  - Correct answer shown
  - Optional explanation callout
- Retake button if allowed

---

### 5. Student Dashboard

**Location:** `app/Livewire/Dashboard.php` + `resources/views/livewire/dashboard.blade.php`

**Features:**
- **Stats Cards** (3-column grid):
  - Total Attempts
  - Average Score
  - Pass Rate
- **Attempt History Table** with:
  - Quiz name
  - Score (color-coded)
  - Correct/Wrong count
  - Time taken
  - Date
  - Pass/Fail badge
  - "View" action
- **Category Performance** table with:
  - Category name
  - Questions answered
  - % Correct with visual progress bar

**Stats Query Pattern:**
```php
public function getStatsProperty(): object
{
    $stats = Attempt::query()
        ->where('user_id', auth()->id())
        ->whereNotNull('submitted_at')
        ->selectRaw('
            COUNT(*) as total_attempts,
            COALESCE(AVG(score), 0) as avg_score,
            SUM(CASE WHEN score >= 70 THEN 1 ELSE 0 END) as passed_count
        ')
        ->first();
    // ...
}
```

---

### 6. Admin Dashboard (Filament Widgets)

**Location:** `app/Filament/Widgets/`

**StatsOverviewWidget:**
- Total Students
- Total Quizzes
- Total Attempts
- Average Score

**RecentAttemptsWidget:**
- Table showing recent quiz attempts across all users
- User name, quiz title, score, status, date

---

### 7. CSS/Styling Patterns

**Card Component Pattern:**
```html
<div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
    <!-- content -->
</div>
```

**Stats Card Pattern:**
```html
<div class="flex items-center gap-4">
    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
        <!-- icon -->
    </div>
    <div>
        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Label</flux:text>
        <div class="text-2xl font-bold text-neutral-900 dark:text-white">Value</div>
    </div>
</div>
```

**Answer Option Pattern:**
```html
<label class="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-4 
              transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800
              {{ selected ? 'bg-blue-50 border-blue-500 dark:bg-blue-950' : '' }}">
    <input type="radio" ...>
    <span class="flex-1 text-sm">{{ $option->text }}</span>
</label>
```

---

### 8. Service Layer Pattern

**Location:** `app/Services/QuizService.php`

All quiz logic is encapsulated in a service:
- `canUserAttemptQuiz()` - Check permissions
- `startQuiz()` - Create attempt
- `getInProgressAttempt()` - Resume unfinished quiz
- `prepareQuizQuestions()` - Get/shuffle questions
- `saveAnswer()` - Persist answer
- `submitQuiz()` - Calculate score, close attempt
- `calculateScore()` - Score computation

This separates business logic from Livewire components.

---

### 9. Database Schema

**Key Tables:**
- `quizzes` - Quiz metadata (title, time_limit_minutes, shuffle options)
- `questions` - Question text, category, image
- `question_options` - Answer options with `is_correct` flag
- `question_quiz` - Many-to-many pivot
- `attempts` - User quiz attempts (started_at, submitted_at, score)
- `attempt_answers` - User's answer per question

---

## 🚀 Implementation Priority

1. ✅ **Quiz Taking Interface** (COMPLETED)
   - One-question-per-page view
   - Question navigator grid (configurable position: bottom, left, right, top, hidden)
   - Progress bar
   - Timer (per-question or total duration)
   - Auto-advance on answer option

2. ✅ **Quiz Results Page** (COMPLETED)
   - Score summary with pass/fail badge
   - Question breakdown with correct/incorrect indicators
   - Explanation callouts
   - Retake button

3. ✅ **Student Dashboard** (COMPLETED)
   - QuizStatsWidget: quizzes taken, attempts, avg score, pass rate
   - QuizAttemptHistoryWidget: recent attempts table
   - CourseQuizPerformanceWidget: performance by course with progress bars

4. ✅ **Admin Dashboard Enhancements** (COMPLETED)
   - AdminStatsOverviewWidget: students, quizzes, attempts, avg score
   - RecentQuizAttemptsWidget: recent attempts across all users

---

## 🆕 Phase 5: Quiz List & Polish Implementation Plan

### Overview
Enhance the quiz list display within courses to match the demo's polished card-based design with:
- Visual quiz cards with metadata badges
- Last attempt score display
- Progress indicators
- "Resume" functionality for in-progress quizzes

### Tasks

#### 5.1 Enhanced Quiz List Component
**File:** `app/Infolists/Components/ListQuizzes.php`

**Changes:**
- Load user's attempts with the quizzes
- Include question count
- Calculate time estimate based on duration settings

```php
public function course($course): static
{
    $userId = auth()->id();
    
    $this->quizzes = Quiz::where('course_id', $course->id)
        ->published()
        ->withCount('questions')
        ->with(['tests' => function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orderByDesc('created_at');
        }])
        ->get();

    return $this->configure();
}
```

#### 5.2 Quiz Card Blade Template
**File:** `resources/views/filament/infolists/components/list-quizzes.blade.php`

**New Features:**
- [ ] Card-based layout with rounded borders
- [ ] Quiz title and description
- [ ] Badges: question count, time limit, attempts allowed
- [ ] Last attempt score display (if exists)
- [ ] In-progress indicator with "Resume" button
- [ ] Completed indicator with "Retake" button (if allowed)
- [ ] Disabled state when max attempts reached
- [ ] Dark mode support

**UI Pattern (from demo):**
```html
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @foreach ($quizzes as $quiz)
        <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 
                    dark:border-gray-700 dark:bg-gray-900">
            <!-- Header -->
            <h3 class="text-lg font-semibold">{{ $quiz->title }}</h3>
            <p class="text-gray-600 dark:text-gray-400">{{ $quiz->description }}</p>
            
            <!-- Badges -->
            <div class="flex flex-wrap gap-2 mt-3">
                <span class="badge">{{ $quiz->questions_count }} Questions</span>
                @if($quiz->total_duration)
                    <span class="badge">{{ $quiz->total_duration }} min</span>
                @endif
            </div>
            
            <!-- Last Attempt -->
            @if($lastAttempt)
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <span>Last Score: {{ $lastAttempt->score }}%</span>
                </div>
            @endif
            
            <!-- Action Button -->
            <div class="mt-auto pt-4">
                @if($inProgress)
                    <button>Resume Quiz</button>
                @elseif($canAttempt)
                    <button>{{ $hasAttempted ? 'Retake' : 'Start' }} Quiz</button>
                @else
                    <button disabled>Completed</button>
                @endif
            </div>
        </div>
    @endforeach
</div>
```

#### 5.3 Quiz Attempt Status Helper
**Add to `QuizTakingService.php`:**

```php
public function getQuizStatusForUser(User $user, Quiz $quiz): array
{
    $attempts = $quiz->tests()->where('user_id', $user->id)->get();
    $inProgress = $attempts->whereNull('submitted_at')->first();
    $lastSubmitted = $attempts->whereNotNull('submitted_at')
                              ->sortByDesc('submitted_at')
                              ->first();
    $attemptsUsed = $attempts->whereNotNull('submitted_at')->count();
    $canAttempt = $this->canUserAttemptQuiz($user, $quiz);
    
    return [
        'in_progress' => $inProgress,
        'last_submitted' => $lastSubmitted,
        'attempts_used' => $attemptsUsed,
        'attempts_allowed' => $quiz->attempts_allowed,
        'can_attempt' => $canAttempt,
        'best_score' => $this->getBestAttempt($user, $quiz)?->score_percentage,
    ];
}
```

#### 5.4 Tests for Quiz List
**File:** `tests/Feature/Infolists/ListQuizzesTest.php`

- [ ] Test quiz cards render with correct data
- [ ] Test in-progress quiz shows "Resume" button
- [ ] Test completed quiz with retakes shows "Retake" button
- [ ] Test max attempts reached shows disabled state
- [ ] Test last attempt score displays correctly

---

## 🆕 Phase 6: Additional Quiz Configuration Options

### Overview
Add more quiz customization options to give instructors fine-grained control over quiz behavior.

### New Quiz Settings

#### 6.1 Question Pool / Random Selection
**Purpose:** Allow quizzes to have more questions than are shown, randomly selecting a subset.

**Database Migration:**
```php
Schema::table('quizzes', function (Blueprint $table) {
    $table->integer('questions_per_attempt')->nullable()->after('shuffle_options');
    $table->boolean('show_correct_answers')->default(true)->after('questions_per_attempt');
    $table->boolean('show_explanations')->default(true)->after('show_correct_answers');
    $table->integer('passing_score')->nullable()->after('show_explanations');
    $table->boolean('require_passing_to_proceed')->default(false)->after('passing_score');
});
```

**Quiz Model Updates:**
```php
protected $fillable = [
    // ... existing
    'questions_per_attempt',     // Show N random questions from pool
    'show_correct_answers',      // Show correct answers on results page
    'show_explanations',         // Show explanations on results page
    'passing_score',             // Minimum % to pass (e.g., 70)
    'require_passing_to_proceed', // Must pass to continue in course
];
```

**Service Changes (`QuizTakingService`):**
```php
public function prepareQuizQuestions(Quiz $quiz): Collection
{
    $questions = $quiz->questions;
    
    if ($quiz->shuffle_questions) {
        $questions = $questions->shuffle();
    }
    
    // Random selection from pool
    if ($quiz->questions_per_attempt && $quiz->questions_per_attempt < $questions->count()) {
        $questions = $questions->random($quiz->questions_per_attempt);
    }
    
    return $questions->values();
}
```

#### 6.2 Feedback Timing Options
**Purpose:** Control when students see their results.

**New Enum: `FeedbackTiming`**
```php
enum FeedbackTiming: string
{
    case Immediate = 'immediate';      // Show after each question
    case AfterSubmit = 'after_submit'; // Show after quiz submission
    case AfterDeadline = 'after_deadline'; // Show after end_time
    case Never = 'never';              // Never show correct answers
}
```

**Database Migration:**
```php
$table->string('feedback_timing')->default('after_submit');
```

#### 6.3 Admin Quiz Form Updates
**File:** `app/Filament/Resources/QuizResource.php`

Add new form sections:

```php
Forms\Components\Section::make('Question Pool Settings')
    ->schema([
        Forms\Components\TextInput::make('questions_per_attempt')
            ->label('Questions Per Attempt')
            ->helperText('Leave empty to show all questions')
            ->numeric()
            ->minValue(1),
    ]),

Forms\Components\Section::make('Results & Feedback')
    ->schema([
        Forms\Components\Toggle::make('show_correct_answers')
            ->label('Show Correct Answers')
            ->default(true),
        Forms\Components\Toggle::make('show_explanations')
            ->label('Show Explanations')
            ->default(true),
        Forms\Components\Select::make('feedback_timing')
            ->options(FeedbackTiming::class)
            ->default('after_submit'),
    ]),

Forms\Components\Section::make('Passing Requirements')
    ->schema([
        Forms\Components\TextInput::make('passing_score')
            ->label('Passing Score (%)')
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->suffix('%'),
        Forms\Components\Toggle::make('require_passing_to_proceed')
            ->label('Require Passing to Continue Course')
            ->helperText('Student must pass this quiz to access next lessons'),
    ]),
```

#### 6.4 Certificate Integration
**Purpose:** Auto-generate certificates when course quizzes are completed with passing scores.

**New Model Method (`Course.php`):**
```php
public function hasCompletedAllQuizzes(User $user): bool
{
    $quizzes = $this->quizzes()->published()->get();
    
    foreach ($quizzes as $quiz) {
        $bestAttempt = $quiz->tests()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('result')
            ->first();
            
        if (!$bestAttempt) {
            return false;
        }
        
        if ($quiz->passing_score) {
            $percentage = ($bestAttempt->result / $quiz->questions()->count()) * 100;
            if ($percentage < $quiz->passing_score) {
                return false;
            }
        }
    }
    
    return true;
}
```

**Observer for Auto-Certification:**
```php
// app/Observers/TestObserver.php
public function updated(Test $test): void
{
    // Check if quiz was just submitted
    if ($test->wasChanged('submitted_at') && $test->submitted_at) {
        $this->checkForCertificateEligibility($test);
    }
}

protected function checkForCertificateEligibility(Test $test): void
{
    $course = $test->quiz->course;
    $user = $test->user;
    
    if (!$course || !$course->hasCompletedAllQuizzes($user)) {
        return;
    }
    
    // Check if certificate already exists
    if (Certificate::where('user_id', $user->id)
                   ->where('course_id', $course->id)
                   ->exists()) {
        return;
    }
    
    // Generate certificate
    $certificate = Certificate::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'issued_at' => now(),
    ]);
    
    $certificate->certificate_number = $certificate->generateCertificateNumber();
    $certificate->save();
    
    // Optionally generate PDF
    // app(CertificateGenerator::class)->generate($certificate);
}
```

#### 6.5 Implementation Tasks

| Task | Priority | Estimated Time |
|------|----------|----------------|
| Create migration for new quiz columns | High | 15 min |
| Create `FeedbackTiming` enum | High | 10 min |
| Update Quiz model with new fields | High | 15 min |
| Update `QuizTakingService` for question pools | High | 30 min |
| Update QuizResource admin form | Medium | 45 min |
| Update ViewStudentResult to respect feedback settings | Medium | 30 min |
| Implement certificate auto-generation | Medium | 45 min |
| Create TestObserver for certificate trigger | Medium | 30 min |
| Write tests for new functionality | High | 1 hour |
| Update quiz list component | Medium | 30 min |

---

## 📝 Notes

- The demo uses **Livewire Flux** ($299 license) for UI components. We can replicate the styling using Tailwind without Flux.
- All views support **dark mode** via `dark:` Tailwind classes
- The service layer pattern is clean and testable
- Timer uses Alpine.js with Livewire entanglement

---

## 🗓️ Implementation Order

### Recommended Sequence:

1. **Phase 5.1-5.4**: Quiz List Enhancement (2-3 hours)
   - Improves student experience immediately
   - Low risk, mostly UI changes

2. **Phase 6.1**: Question Pool / Random Selection (1 hour)
   - High value for instructors
   - Relatively simple to implement

3. **Phase 6.3**: Admin Form Updates (1 hour)
   - Exposes new settings to admins
   - Required for other features

4. **Phase 6.2**: Feedback Timing Options (45 min)
   - Enhances privacy/control for exams
   - Moderate complexity

5. **Phase 6.4**: Certificate Integration (1.5 hours)
   - Adds completion reward
   - Depends on quiz completion tracking

6. **Testing & Polish** (1-2 hours)
   - Ensure all tests pass
   - Browser testing
   - Edge case handling
