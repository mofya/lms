<x-filament-panels::page>
    <div
        x-data="{
            remainingSeconds: {{ $totalDuration ?? ($secondsPerQuestion ?? 60) }},
            totalTimeMode: {{ $totalDuration ? 'true' : 'false' }},
            secondsPerQuestion: {{ $secondsPerQuestion ?? 60 }},
            formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${mins}:${secs.toString().padStart(2, '0')}`;
            },
            init() {
                setInterval(() => {
                    if (this.remainingSeconds > 1) {
                        this.remainingSeconds--;
                    } else {
                        if (this.totalTimeMode) {
                            $wire.dispatch('timer-expired');
                        } else {
                            if ($wire.currentQuestionIndex < $wire.questionsCount - 1) {
                                this.remainingSeconds = this.secondsPerQuestion;
                                $wire.changeQuestion();
                            } else {
                                $wire.dispatch('timer-expired');
                            }
                        }
                    }
                }, 1000);
            }
        }"
        class="space-y-6"
    >
        {{-- Header with Timer and Attempt Info --}}
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $quiz->title }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Question {{ $currentQuestionIndex + 1 }} of {{ $this->questionsCount }}
                    @if ($this->attemptNumber > 1)
                        <span class="ml-2 text-xs text-gray-500">(Attempt #{{ $this->attemptNumber }})</span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Saving Indicator --}}
                <div
                    wire:loading
                    wire:target="questionsAnswers"
                    class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400"
                >
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </div>

                {{-- Timer Badge --}}
                <div
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                    :class="remainingSeconds <= 60 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="formatTime(remainingSeconds)" class="font-bold text-lg"></span>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        @if ($quiz->show_progress_bar)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->answeredCount }} / {{ $this->questionsCount }} answered
                    </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full bg-primary-600 transition-all duration-500 ease-out dark:bg-primary-500"
                        style="width: {{ $this->progressPercentage }}%"
                    ></div>
                </div>
            </div>
        @endif

        {{-- Main Content Area - Layout based on navigator position --}}
        @php
            $navigatorPosition = $navigatorPosition ?? 'bottom';
            $showNavigator = $quiz->show_one_question_at_a_time && $quiz->allow_question_navigation && $navigatorPosition !== 'hidden';
        @endphp

        <div class="@if($showNavigator && in_array($navigatorPosition, ['left', 'right'])) flex gap-6 @endif">

            {{-- Left Navigator --}}
            @if ($showNavigator && $navigatorPosition === 'left')
                @include('filament.student.pages.partials.question-navigator', ['position' => 'sidebar'])
            @endif

            {{-- Question Content --}}
            <div class="@if($showNavigator && in_array($navigatorPosition, ['left', 'right'])) flex-1 @else w-full @endif">

                {{-- Top Navigator --}}
                @if ($showNavigator && $navigatorPosition === 'top')
                    @include('filament.student.pages.partials.question-navigator', ['position' => 'horizontal'])
                @endif

                {{-- Question Display --}}
                <div
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    x-data="{ showFeedback: false }"
                    @answer-saved.window="showFeedback = true; setTimeout(() => showFeedback = false, 1500)"
                >
                    {{-- Answer Saved Feedback --}}
                    <div
                        x-show="showFeedback"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute top-4 right-4 flex items-center gap-2 rounded-lg bg-emerald-100 px-3 py-2 text-sm font-medium text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Saved
                    </div>

                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $currentQuestion->question_text }}
                    </h3>

                    {{-- Code Snippet --}}
                    @if ($currentQuestion->code_snippet)
                        <pre class="mb-6 rounded-lg border-2 border-gray-200 bg-gray-100 p-4 text-sm font-mono overflow-x-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $currentQuestion->code_snippet }}</pre>
                    @endif

                    {{-- Answer Options --}}
                    <div class="space-y-3">
                        @if ($currentQuestion->type === 'single_answer')
                            <div class="mt-4">
                                <x-filament::input.wrapper>
                                    <x-filament::input
                                        type="text"
                                        wire:model.live.debounce.500ms="questionsAnswers.{{ $currentQuestionIndex }}"
                                        placeholder="Type your answer here"
                                        class="w-full"
                                    />
                                </x-filament::input.wrapper>
                            </div>

                        @elseif ($currentQuestion->type === 'checkbox')
                            @foreach ($currentQuestion->questionOptions as $option)
                                <label
                                    wire:key="option-{{ $currentQuestionIndex }}-{{ $option->id }}"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 transition-all duration-200 hover:bg-gray-50 hover:border-gray-300 dark:border-gray-700 dark:hover:bg-gray-800 dark:hover:border-gray-600
                                    {{ in_array($option->id, $questionsAnswers[$currentQuestionIndex] ?? []) ? 'bg-primary-50 border-primary-500 ring-2 ring-primary-500/20 dark:bg-primary-950 dark:border-primary-500' : '' }}"
                                >
                                    <x-filament::input.checkbox
                                        id="checkbox-{{ $option->id }}"
                                        value="{{ $option->id }}"
                                        wire:model.live="questionsAnswers.{{ $currentQuestionIndex }}"
                                    />
                                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $option->option }}</span>
                                </label>
                            @endforeach

                        @else
                            {{-- Multiple Choice (Radio) --}}
                            @foreach ($currentQuestion->questionOptions as $option)
                                <label
                                    wire:key="option-{{ $currentQuestionIndex }}-{{ $option->id }}"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 transition-all duration-200 hover:bg-gray-50 hover:border-gray-300 dark:border-gray-700 dark:hover:bg-gray-800 dark:hover:border-gray-600
                                    {{ isset($questionsAnswers[$currentQuestionIndex]) && $questionsAnswers[$currentQuestionIndex] == $option->id ? 'bg-primary-50 border-primary-500 ring-2 ring-primary-500/20 dark:bg-primary-950 dark:border-primary-500' : '' }}"
                                >
                                    <x-filament::input.radio
                                        id="radio-{{ $option->id }}"
                                        value="{{ $option->id }}"
                                        name="questionsAnswers.{{ $currentQuestionIndex }}"
                                        wire:model.live="questionsAnswers.{{ $currentQuestionIndex }}"
                                    />
                                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ $option->option }}</span>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- Bottom Navigator --}}
                @if ($showNavigator && $navigatorPosition === 'bottom')
                    @include('filament.student.pages.partials.question-navigator', ['position' => 'horizontal'])
                @endif

                {{-- Navigation Buttons --}}
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2">
                        @if ($quiz->allow_question_navigation && $currentQuestionIndex > 0)
                            <x-filament::button
                                type="button"
                                color="gray"
                                wire:click="previousQuestion"
                                wire:loading.attr="disabled"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Previous
                            </x-filament::button>
                        @endif

                        @if ($currentQuestionIndex < $this->questionsCount - 1)
                            <x-filament::button
                                type="button"
                                color="gray"
                                wire:click="changeQuestion"
                                wire:loading.attr="disabled"
                                x-on:click="if (!@js($totalDuration)) { remainingSeconds = secondsPerQuestion; }"
                            >
                                Next
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </x-filament::button>
                        @endif
                    </div>

                    <x-filament::button
                        type="button"
                        color="success"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        wire:confirm="Are you sure you want to submit this quiz? You cannot change your answers after submission."
                    >
                        <span wire:loading.remove wire:target="submit">Submit Quiz</span>
                        <span wire:loading wire:target="submit">
                            <svg class="mr-2 h-4 w-4 animate-spin inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Submitting...
                        </span>
                    </x-filament::button>
                </div>
            </div>

            {{-- Right Navigator --}}
            @if ($showNavigator && $navigatorPosition === 'right')
                @include('filament.student.pages.partials.question-navigator', ['position' => 'sidebar'])
            @endif
        </div>

        {{-- Warning when time is low --}}
        <div
            x-show="remainingSeconds <= 30 && remainingSeconds > 0"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            class="fixed bottom-4 right-4 z-50 rounded-lg bg-red-600 px-4 py-3 text-white shadow-lg"
        >
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium">Time is running out!</span>
                <span x-text="remainingSeconds + 's'" class="font-bold"></span>
            </div>
        </div>

        {{-- Keyboard shortcuts info (optional) --}}
        @if ($quiz->allow_question_navigation)
            <div class="text-center text-xs text-gray-400 dark:text-gray-500">
                <span class="hidden sm:inline">
                    Tip: Click question numbers to navigate quickly
                </span>
            </div>
        @endif
    </div>
</x-filament-panels::page>
