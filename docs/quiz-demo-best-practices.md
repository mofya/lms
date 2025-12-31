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

## 📝 Notes

- The demo uses **Livewire Flux** ($299 license) for UI components. We can replicate the styling using Tailwind without Flux.
- All views support **dark mode** via `dark:` Tailwind classes
- The service layer pattern is clean and testable
- Timer uses Alpine.js with Livewire entanglement
