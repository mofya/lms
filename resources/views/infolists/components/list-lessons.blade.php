<div class="space-y-2">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Lessons</h3>

    @if ($lessons && $lessons->count() > 0)
        <div class="flex flex-col space-y-0.5">
            @foreach($lessons as $index => $lesson)
                @php
                    $url = \App\Filament\Student\Resources\CourseResource::getUrl('lessons.view', [
                        'parent' => $course,
                        'record' => $lesson,
                    ]);
                @endphp
                <div class="rounded px-2 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <a href="{{ $url }}" class="flex flex-row items-center gap-2 py-1.5">
                        <div class="w-5 text-right shrink-0 font-mono text-gray-500 dark:text-gray-400">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $lesson->title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1">
                                    @if($lesson->type === \App\Models\Lesson::TYPE_VIDEO)
                                        🎬 Video
                                    @else
                                        📄 Text
                                    @endif
                                </span>
                                @if($lesson->duration_seconds)
                                    <span>• {{ gmdate('i:s', $lesson->duration_seconds) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-400">No lessons available for this course.</p>
    @endif
</div>
