@php
    /** @var \App\Models\Lesson $lesson */
    $lesson = $getLesson();
    $embedUrl = $getEmbedUrl();
@endphp

<div {{ $attributes->class('space-y-6') }}>
    @if($isVideoLesson() && $embedUrl)
        <div class="aspect-video rounded-lg overflow-hidden bg-black shadow">
            <iframe
                class="w-full h-full"
                src="{{ $embedUrl }}"
                title="{{ $lesson->title }}"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
        </div>
    @endif

    @if($lesson->lesson_text)
        <article class="prose prose-invert max-w-none">
            {!! $lesson->lesson_text !!}
        </article>
    @endif

    @if($lesson->duration_seconds)
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Duration: {{ gmdate('i:s', $lesson->duration_seconds) }}
        </div>
    @endif
</div>

