<x-html>
    <div class="flex flex-col min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <main class="grow">
            <div class="max-w-7xl mx-auto my-12 px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        @include('layouts.footer')
    </div>
</x-html>