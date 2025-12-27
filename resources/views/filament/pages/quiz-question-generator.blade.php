<x-filament-panels::page>
    <div class="flex items-center justify-between mb-4">
        <div class="text-sm text-gray-600 dark:text-gray-300">
            Step {{ $step }} of 2
        </div>
        <div class="flex items-center gap-2">
            @if ($step === 2)
                <x-filament::button color="gray" wire:click="restart" size="sm">Back to form</x-filament::button>
                <x-filament::button color="primary" wire:click="saveApproved" size="sm">Save all</x-filament::button>
            @endif
        </div>
    </div>

    @if ($step === 1)
        <form wire:submit.prevent="submit" class="space-y-4">
            {{ $this->form }}
            <div class="flex items-center gap-3">
                <x-filament::button type="submit">Generate draft questions</x-filament::button>
                <div wire:loading wire:target="submit" class="flex items-center space-x-2 text-sm text-gray-600">
                    <svg class="animate-spin h-4 w-4 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Generating questions...</span>
                </div>
            </div>
        </form>
    @else
        <div class="space-y-4">
            @forelse ($draftQuestions as $index => $question)
                <div class="rounded-lg border border-gray-200/70 dark:border-gray-700 bg-white/80 dark:bg-gray-900/60 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Question {{ $index + 1 }}
                            <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs text-gray-700 dark:text-gray-300">
                                {{ ucfirst(str_replace('_',' ', $question['type'] ?? 'multiple_choice')) }}
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <x-filament::button color="gray" size="sm" wire:click="regenerateQuestion({{ $index }})">Regenerate</x-filament::button>
                            <x-filament::button color="danger" size="sm" wire:click="removeQuestion({{ $index }})">Delete</x-filament::button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Question Text</label>
                        <textarea class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800" rows="3" wire:model.defer="draftQuestions.{{ $index }}.question_text"></textarea>
                    </div>

                    <div class="grid md:grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Answer Explanation</label>
                            <textarea class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800" rows="2" wire:model.defer="draftQuestions.{{ $index }}.answer_explanation"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">More Info Link</label>
                            <input type="text" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800" wire:model.defer="draftQuestions.{{ $index }}.more_info_link">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Options</label>
                        <div class="space-y-2">
                            @foreach ($question['options'] ?? [] as $optIndex => $option)
                                <div class="flex items-center gap-2">
                                    <input type="text" class="flex-1 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-800" wire:model.defer="draftQuestions.{{ $index }}.options.{{ $optIndex }}.option">
                                    <label class="inline-flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                                        <input type="checkbox" wire:model="draftQuestions.{{ $index }}.options.{{ $optIndex }}.correct">
                                        Correct
                                    </label>
                                    <x-filament::button color="danger" size="xs" wire:click="removeOption({{ $index }}, {{ $optIndex }})">Remove</x-filament::button>
                                </div>
                            @endforeach
                        </div>
                        <x-filament::button color="gray" size="sm" wire:click="addOption({{ $index }})">Add option</x-filament::button>
                    </div>
                </div>
            @empty
                <div class="rounded border border-dashed border-gray-300 dark:border-gray-700 p-4 text-sm text-gray-600 dark:text-gray-300">
                    No draft questions yet. Generate to start.
                </div>
            @endforelse

            @if (count($draftQuestions) > 0)
                <div class="flex items-center justify-end gap-2">
                    <x-filament::button color="gray" wire:click="restart">Back to form</x-filament::button>
                    <x-filament::button color="primary" wire:click="saveApproved">Save all</x-filament::button>
                </div>
            @endif
        </div>
    @endif

    @if ($resultMessage)
        <div class="mt-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
            {{ $resultMessage }}
        </div>
    @endif
</x-filament-panels::page>
