<x-app-layout>
    <div class="grid grid-cols-3">
        <div class="col-span-2">
            <h1 class="text-3xl font-semibold">{{ $course->title }}</h1>
            <div class="mt-4">{!! $course->description !!}</div>
        </div>
        <div class="flex flex-col space-y-0.5">
            @foreach($lessons as $index => $lesson)
                <div class="hover:bg-gray-200 hover:text-primary-600 px-2 rounded">
                    <a href="{{ route('filament.student.resources.courses.lessons.view', [$course, $lesson]) }}" class="flex flex-row">
                        <div class="w-5 mr-2 text-right shrink-0 font-mono">{{ $index + 1 }}</div>
                        <div class="">{{ $lesson->title }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>