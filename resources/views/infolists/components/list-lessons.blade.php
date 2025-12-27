<div {{ $attributes }}>
    <dt class="fi-in-entry-wrp-label inline-flex items-center gap-x-3">
        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
            {{ $getName() }}
        </span>
    </dt>
    <div class="flex flex-col space-y-0.5 mt-2">
        @foreach($getLessons() as $index => $lesson)
            <div @class([
                    'rounded px-2',
                    'bg-primary-600 text-white' => $isActive($lesson),
                    'hover:bg-gray-100 hover:text-primary-600' => !$isActive($lesson)
                ])>
                <a href="{{ $getUrl($lesson) }}" class="flex flex-row items-center gap-2 py-1.5">
                    <div class="w-5 text-right shrink-0 font-mono">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">{{ $lesson->title }}</div>
                        <div class="text-xs opacity-80 flex items-center gap-2">
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
</div>
