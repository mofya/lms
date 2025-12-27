<div class="space-y-2">
    <h3 class="text-lg font-bold">Quizzes</h3>

    @forelse ($quizzes as $quiz)
        <div class="border p-3 rounded-lg">
            <h4 class="font-semibold">{{ $quiz->title }}</h4>
            <p>Attempts Allowed: {{ $quiz->attempts_allowed }}</p>
            <p>Questions: {{ $quiz->questions()->count() }}</p>
            <x-filament::button
                    color="primary"
                    tag="a"
                    href="{{ route('filament.student.pages.take-quiz', ['record' => $quiz->id]) }}"
            >
                Start Quiz
            </x-filament::button>
        </div>
    @empty
        <p>No quizzes available for this course.</p>
    @endforelse
</div>