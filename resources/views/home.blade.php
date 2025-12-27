<x-app-layout>
    <h1 class="text-3xl text-center font-semibold">All Courses</h1>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-8 mt-8">
        @foreach($courses as $course)
            <a href="{{ route('courses.show', $course) }}">
                <div class="bg-white border rounded p-3">
                    <img src="{{ $course->getFirstMediaUrl('featured_image') }}" alt="" class="rounded object-cover w-full h-auto aspect-video">
                    <div class="font-semibold text-lg">{{ $course->title }}</div>
                    <div class="mt-2">{{ $course->description }}</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $courses->links() }}</div>
</x-app-layout>