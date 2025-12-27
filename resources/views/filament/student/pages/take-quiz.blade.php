<x-filament-panels::page>
    <div
        x-data="{
            secondsLeft: {{ $quiz->shouldUseTotalDuration() ? $quiz->total_duration : $quiz->duration_per_question ?? 60 }},
            totalTimeMode: {{ $quiz->shouldUseTotalDuration() ? 'true' : 'false' }}
        }"
        x-init="setInterval(() => {
            if (totalTimeMode) {
                if (secondsLeft > 1) {
                    secondsLeft--;
                } else {
                    console.log('Time is up, submitting quiz...');
                    $wire.submit();
                }
            } else {
                if (secondsLeft > 1) {
                    secondsLeft--;
                } else {
                    console.log('Time up for this question...');
                    if ($wire.currentQuestionIndex < $wire.questionsCount - 1) {
                        secondsLeft = {{ $quiz->duration_per_question ?? 60 }};
                        $wire.changeQuestion();
                    } else {
                        console.log('No more questions, submitting quiz...');
                        $wire.submit();
                    }
                }
            }
        }, 1000);">

        <!-- Timer Display -->
        <div class="mb-4">
            <div class="flex items-center gap-2">
                <span class="text-lg font-semibold">Time left:</span>
                <span x-text="secondsLeft" class="text-2xl font-bold" :class="secondsLeft <= 10 ? 'text-red-600' : 'text-emerald-600'"></span>
                <span class="text-lg">seconds</span>
            </div>
            <div x-show="secondsLeft <= 5" class="mt-1 text-red-500 font-bold animate-pulse">
                ⚠️ Hurry up! Time is running out.
            </div>
        </div>

        <!-- Quiz Question Section -->
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                <span class="font-bold text-gray-700">Question {{ $currentQuestionIndex + 1 }} of {{ $this->questionsCount }}</span>
            </x-slot>

            <h2 class="mb-4 text-xl font-semibold text-gray-900">{{ $currentQuestion->question_text }}</h2>

            <!-- Show code snippet if present -->
            @if ($currentQuestion->code_snippet)
                <pre class="mb-4 border-2 border-gray-200 bg-gray-100 dark:bg-gray-800 dark:border-gray-700 p-3 rounded-lg text-sm font-mono overflow-x-auto">{{ $currentQuestion->code_snippet }}</pre>
            @endif

            <!-- Single Answer: text input -->
            @if ($currentQuestion->type === 'single_answer')
                <div class="mt-4">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                            placeholder="Type your answer here"
                        />
                    </x-filament::input.wrapper>
                </div>

            <!-- Checkbox: allow multiple selections -->
            @elseif ($currentQuestion->type === 'checkbox')
                <div class="space-y-2 mt-4">
                    @foreach($currentQuestion->questionOptions as $option)
                        <div wire:key="option.{{ $currentQuestionIndex }}.{{ $option->id }}" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <label for="checkbox-option-{{ $option->id }}" class="cursor-pointer flex items-center gap-3">
                                <x-filament::input.checkbox
                                    id="checkbox-option-{{ $option->id }}"
                                    value="{{ $option->id }}"
                                    wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                                />
                                <span class="text-base">{{ $option->option }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

            <!-- Multiple Choice: radio buttons (default) -->
            @else
                <div class="space-y-2 mt-4">
                    @foreach($currentQuestion->questionOptions as $option)
                        <div wire:key="option.{{ $currentQuestionIndex }}.{{ $option->id }}" class="p-3 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <label for="radio-option-{{ $option->id }}" class="cursor-pointer flex items-center gap-3">
                                <x-filament::input.radio
                                    id="radio-option-{{ $option->id }}"
                                    value="{{ $option->id }}"
                                    name="questionsAnswers.{{ $currentQuestionIndex }}"
                                    wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                                />
                                <span class="text-base">{{ $option->option }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        <!-- Navigation Buttons -->
        <div class="mt-6 flex justify-between items-center">
            <div>
                @if ($currentQuestionIndex > 0)
                    <x-filament::button type="button" color="gray" wire:click="previousQuestion">
                        ← Previous
                    </x-filament::button>
                @endif
            </div>

            <div>
                @if ($currentQuestionIndex < $this->questionsCount - 1)
                    <x-filament::button type="button" x-on:click="secondsLeft = {{ $quiz->duration_per_question ?? 60 }}; $wire.changeQuestion();">
                        Next Question →
                    </x-filament::button>
                @else
                    <x-filament::button type="submit" wire:click="submit" color="success">
                        ✓ Submit Quiz
                    </x-filament::button>
                @endif
            </div>
        </div>

        <!-- Progress indicator -->
        <div class="mt-6">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>Progress:</span>
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300" style="width: {{ (($currentQuestionIndex + 1) / $this->questionsCount) * 100 }}%"></div>
                </div>
                <span>{{ $currentQuestionIndex + 1 }}/{{ $this->questionsCount }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
