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

        <div class="mb-2">
            Time left for this question: <span x-text="secondsLeft" class="font-bold"></span> sec.
        </div>
        <div x-show="secondsLeft <= 5" class="text-red-500 font-bold">
            Hurry up! Time is running out.
        </div>

        <x-filament::section class="mt-6">
            <span class="font-bold">Question {{ $currentQuestionIndex + 1 }} of {{ $this->questionsCount }}:</span>
            <h2 class="mb-4 text-2xl">{{ $currentQuestion->question_text }}</h2>

            @if ($currentQuestion->code_snippet)
                <pre class="mb-4 border-2 border-gray-100 bg-gray-50 p-2">
                    {{ $currentQuestion->code_snippet }}
                </pre>
            @endif

            @if ($currentQuestion->type === 'single_answer')
                <!-- Single Answer: text input -->
                <div>
                    <x-filament::input
                            type="text"
                            wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                            placeholder="Type your answer here"
                    />
                </div>
            @elseif ($currentQuestion->type === 'checkbox')
                <!-- Checkbox: allow multiple selections -->
                @foreach($currentQuestion->questionOptions as $option)
                    <div wire:key="option.{{ $currentQuestionIndex }}.{{ $option->id }}">
                        <label for="option.{{ $option->id }}">
                            <x-filament::input.checkbox
                                    id="option.{{ $option->id }}"
                                    value="{{ $option->id }}"
                                    name="questionsAnswers.{{ $currentQuestionIndex }}[]"
                                    wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                            />
                            <span>{{ $option->option }}</span>
                        </label>
                    </div>
                @endforeach
            @else
                <!-- Multiple Choice: radio buttons -->
                @foreach($currentQuestion->questionOptions as $option)
                    <div wire:key="option.{{ $option->id }}">
                        <label for="option.{{ $option->id }}">
                            <x-filament::input.radio
                                    id="option.{{ $option->id }}"
                                    value="{{ $option->id }}"
                                    name="questionsAnswers.{{ $currentQuestionIndex }}"
                                    wire:model="questionsAnswers.{{ $currentQuestionIndex }}"
                            />
                            <span>{{ $option->option }}</span>
                        </label>
                    </div>
                @endforeach
            @endif
        </x-filament::section>

        <div class="mt-6">
            @if ($currentQuestionIndex < $this->questionsCount - 1)
                <x-filament::button type="button" x-on:click="$wire.changeQuestion()">
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