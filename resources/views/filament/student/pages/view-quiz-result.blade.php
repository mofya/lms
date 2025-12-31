<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Score Summary Card --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white p-8 dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col items-center gap-6 md:flex-row md:items-start md:justify-between">
                <div class="flex flex-col items-center gap-4 md:items-start">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $this->record->quiz->title }}
                    </h2>

                    <div class="flex items-center gap-6">
                        {{-- Score Display --}}
                        <div class="text-center">
                            <div class="text-5xl font-bold {{ $this->isPassed() ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500' }}">
                                {{ $this->getScorePercentage() }}%
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Your Score</p>
                        </div>

                        <div class="h-16 w-px bg-gray-200 dark:bg-gray-700"></div>

                        {{-- Correct/Wrong Counts --}}
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ $this->getCorrectCount() }} Correct</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ $this->getWrongCount() }} Wrong</span>
                            </div>
                        </div>
                    </div>

                    {{-- Time and Status Badges --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Time: {{ $this->getFormattedTime() }}
                        </span>

                        @if ($this->isPassed())
                            <span class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1 text-sm font-medium text-white dark:bg-emerald-500">
                                Passed
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1 text-sm font-medium text-white dark:bg-red-500">
                                Failed
                            </span>
                        @endif

                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Attempt {{ $this->record->attempt_number ?? 1 }} of {{ $this->record->quiz->attempts_allowed }}
                        </span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col gap-2">
                    @if ($this->canRetakeQuiz())
                        <x-filament::button
                            wire:click="retakeQuiz"
                            color="primary"
                        >
                            Retake Quiz
                        </x-filament::button>
                    @endif

                    <x-filament::button
                        :href="route('filament.student.resources.student-results.index')"
                        tag="a"
                        color="gray"
                    >
                        Back to Results
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Question Breakdown --}}
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Question Breakdown</h3>

            @foreach ($this->record->testAnswers as $index => $testAnswer)
                @php
                    $question = $testAnswer->question;
                    $isCorrect = $testAnswer->correct;
                    $userOptionId = $testAnswer->option_id;
                    $userAnswer = $testAnswer->user_answer;
                @endphp

                <div class="overflow-hidden rounded-xl border {{ $isCorrect ? 'border-emerald-200 dark:border-emerald-800' : 'border-red-200 dark:border-red-800' }} bg-white dark:bg-gray-900">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            {{-- Correct/Incorrect Icon --}}
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full {{ $isCorrect ? 'bg-emerald-100 dark:bg-emerald-900' : 'bg-red-100 dark:bg-red-900' }}">
                                @if ($isCorrect)
                                    <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @endif
                            </div>

                            <div class="flex-1 space-y-4">
                                {{-- Question Header --}}
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Question {{ $index + 1 }}</span>
                                    <h4 class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $question->question_text }}
                                    </h4>
                                </div>

                                {{-- Code Snippet --}}
                                @if ($question->code_snippet)
                                    <pre class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm font-mono overflow-x-auto dark:border-gray-700 dark:bg-gray-800">{{ $question->code_snippet }}</pre>
                                @endif

                                {{-- Answer Options --}}
                                <div class="space-y-2">
                                    @if ($question->type === 'single_answer')
                                        {{-- Single Answer Display --}}
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2 rounded-lg border p-3
                                                {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950' : 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950' }}">
                                                <span class="flex-1 text-gray-700 dark:text-gray-300">Your answer: <strong>{{ $userAnswer ?? '(no answer)' }}</strong></span>
                                                <span class="inline-flex items-center rounded-lg {{ $isCorrect ? 'bg-emerald-600' : 'bg-primary-600' }} px-2 py-1 text-xs font-medium text-white">
                                                    Your Answer
                                                </span>
                                            </div>
                                            @if (!$isCorrect && $question->correct_answer)
                                                <div class="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 p-3 dark:border-emerald-700 dark:bg-emerald-950">
                                                    <span class="flex-1 text-gray-700 dark:text-gray-300">Correct answer: <strong>{{ $question->correct_answer }}</strong></span>
                                                    <span class="inline-flex items-center rounded-lg bg-emerald-600 px-2 py-1 text-xs font-medium text-white">
                                                        Correct
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        {{-- Multiple Choice / Checkbox Options --}}
                                        @foreach ($question->questionOptions as $option)
                                            @php
                                                $isUserAnswer = false;
                                                if ($question->type === 'checkbox') {
                                                    $selectedIds = json_decode($userAnswer ?? '[]', true) ?? [];
                                                    $isUserAnswer = in_array($option->id, $selectedIds);
                                                } else {
                                                    $isUserAnswer = $userOptionId == $option->id;
                                                }
                                                $isCorrectOption = $option->correct;
                                            @endphp

                                            <div class="flex items-center gap-2 rounded-lg border p-3
                                                {{ $isUserAnswer && $isCorrectOption ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950' : '' }}
                                                {{ $isUserAnswer && !$isCorrectOption ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950' : '' }}
                                                {{ !$isUserAnswer && $isCorrectOption ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950' : '' }}
                                                {{ !$isUserAnswer && !$isCorrectOption ? 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' : '' }}
                                            ">
                                                <span class="flex-1 text-gray-700 dark:text-gray-300">{{ $option->option }}</span>

                                                @if ($isUserAnswer)
                                                    <span class="inline-flex items-center rounded-lg bg-primary-600 px-2 py-1 text-xs font-medium text-white dark:bg-primary-500">
                                                        Your Answer
                                                    </span>
                                                @endif

                                                @if ($isCorrectOption)
                                                    <span class="inline-flex items-center rounded-lg bg-emerald-600 px-2 py-1 text-xs font-medium text-white dark:bg-emerald-500">
                                                        Correct
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Explanation --}}
                                @if ($question->answer_explanation)
                                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                                        <div class="flex items-start gap-2">
                                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <span class="font-medium text-blue-800 dark:text-blue-200">Explanation:</span>
                                                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">{{ $question->answer_explanation }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- More Info Link --}}
                                @if ($question->more_info_link)
                                    <a href="{{ $question->more_info_link }}" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:underline dark:text-primary-400">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Learn more
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Bottom Actions --}}
        <div class="flex items-center justify-center gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
            @if ($this->canRetakeQuiz())
                <x-filament::button
                    wire:click="retakeQuiz"
                    color="primary"
                >
                    Retake Quiz
                </x-filament::button>
            @endif

            <x-filament::button
                :href="route('filament.student.resources.student-results.index')"
                tag="a"
                color="gray"
            >
                Back to All Results
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
