<div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Quizzes</h3>

    @if ($quizzesWithStatus->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-600 dark:text-gray-400">No quizzes available for this course.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
            @foreach ($quizzesWithStatus as $item)
                @php
                    $quiz = $item['quiz'];
                    $status = $item['status'];
                    $inProgress = $status['in_progress'] ?? null;
                    $lastSubmitted = $status['last_submitted'] ?? null;
                    $canAttempt = $status['can_attempt'] ?? true;
                    $hasAttempted = $status['has_attempted'] ?? false;
                    $attemptsUsed = $status['attempts_used'] ?? 0;
                    $attemptsAllowed = $status['attempts_allowed'];
                    $remainingAttempts = $status['remaining_attempts'];
                    $lastScorePercentage = $status['last_score_percentage'] ?? null;
                    $bestScorePercentage = $status['best_score_percentage'] ?? null;
                @endphp

                <div class="flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-1 flex-col gap-3">
                        {{-- Header --}}
                        <div class="flex-1">
                            <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $quiz->title }}
                            </h4>

                            @if ($quiz->description)
                                <p class="mt-1 text-sm text-gray-600 line-clamp-2 dark:text-gray-400">
                                    {{ Str::limit($quiz->description, 100) }}
                                </p>
                            @endif

                            {{-- Badges --}}
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    <x-filament::icon
                                        icon="heroicon-m-question-mark-circle"
                                        class="h-3.5 w-3.5"
                                    />
                                    {{ $quiz->questions_count }} {{ Str::plural('Question', $quiz->questions_count) }}
                                </span>

                                @if ($quiz->total_duration)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <x-filament::icon
                                            icon="heroicon-m-clock"
                                            class="h-3.5 w-3.5"
                                        />
                                        {{ $quiz->total_duration }} min
                                    </span>
                                @endif

                                @if ($attemptsAllowed)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <x-filament::icon
                                            icon="heroicon-m-arrow-path"
                                            class="h-3.5 w-3.5"
                                        />
                                        {{ $attemptsUsed }}/{{ $attemptsAllowed }} Attempts
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <x-filament::icon
                                            icon="heroicon-m-arrow-path"
                                            class="h-3.5 w-3.5"
                                        />
                                        Unlimited
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- In Progress Notice --}}
                        @if ($inProgress)
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon
                                        icon="heroicon-m-play-circle"
                                        class="h-5 w-5 text-amber-600 dark:text-amber-400"
                                    />
                                    <span class="text-sm font-medium text-amber-800 dark:text-amber-300">
                                        Quiz in progress
                                    </span>
                                </div>
                                @if ($inProgress->started_at)
                                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                        Started {{ $inProgress->started_at->diffForHumans() }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Last Attempt Score --}}
                        @if ($lastSubmitted && !$inProgress)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Last Attempt</p>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-sm font-semibold
                                        @if ($lastScorePercentage >= 70)
                                            bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                        @else
                                            bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @endif
                                    ">
                                        {{ $lastScorePercentage }}%
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $lastSubmitted->submitted_at->diffForHumans() }}
                                    </span>
                                </div>
                                @if ($bestScorePercentage && $bestScorePercentage !== $lastScorePercentage)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Best: {{ $bestScorePercentage }}%
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Action Button --}}
                        <div class="mt-auto pt-2">
                            @if ($inProgress)
                                <x-filament::button
                                    tag="a"
                                    href="{{ route('filament.student.pages.take-quiz', ['record' => $quiz->id]) }}"
                                    color="warning"
                                    class="w-full justify-center"
                                >
                                    <x-filament::icon
                                        icon="heroicon-m-play"
                                        class="-ml-1 mr-1.5 h-4 w-4"
                                    />
                                    Resume Quiz
                                </x-filament::button>
                            @elseif ($canAttempt)
                                <x-filament::button
                                    tag="a"
                                    href="{{ route('filament.student.pages.take-quiz', ['record' => $quiz->id]) }}"
                                    color="primary"
                                    class="w-full justify-center"
                                >
                                    <x-filament::icon
                                        icon="{{ $hasAttempted ? 'heroicon-m-arrow-path' : 'heroicon-m-play' }}"
                                        class="-ml-1 mr-1.5 h-4 w-4"
                                    />
                                    {{ $hasAttempted ? 'Retake Quiz' : 'Start Quiz' }}
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    disabled
                                    color="gray"
                                    class="w-full justify-center opacity-50 cursor-not-allowed"
                                >
                                    <x-filament::icon
                                        icon="heroicon-m-check-circle"
                                        class="-ml-1 mr-1.5 h-4 w-4"
                                    />
                                    Completed
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
