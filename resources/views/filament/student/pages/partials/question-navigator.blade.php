@php
    $isSidebar = ($position ?? 'horizontal') === 'sidebar';
@endphp

<div class="@if($isSidebar) w-64 flex-shrink-0 @else mt-6 @endif rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
    <h4 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">Questions</h4>

    <div class="@if($isSidebar) grid grid-cols-4 gap-2 @else grid grid-cols-5 gap-2 sm:grid-cols-8 md:grid-cols-10 lg:grid-cols-12 @endif">
        @foreach ($questions as $index => $question)
            <button
                type="button"
                wire:click="goToQuestion({{ $index }})"
                wire:loading.attr="disabled"
                wire:key="nav-btn-{{ $question->id }}"
                @class([
                    'flex h-10 w-10 items-center justify-center rounded-lg border text-sm font-medium transition-all duration-200',
                    'border-primary-500 bg-primary-600 text-white shadow-md dark:bg-primary-500' => $currentQuestionIndex === $index,
                    'border-emerald-500 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 dark:hover:bg-emerald-900' => $currentQuestionIndex !== $index && $this->isQuestionAnswered($index),
                    'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' => $currentQuestionIndex !== $index && !$this->isQuestionAnswered($index),
                ])
            >
                {{ $index + 1 }}
            </button>
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-1">
            <span class="h-3 w-3 rounded bg-primary-600 dark:bg-primary-500"></span>
            <span>Current</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="h-3 w-3 rounded border border-emerald-500 bg-emerald-50 dark:bg-emerald-950"></span>
            <span>Answered</span>
        </div>
        <div class="flex items-center gap-1">
            <span class="h-3 w-3 rounded border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"></span>
            <span>Unanswered</span>
        </div>
    </div>
</div>
