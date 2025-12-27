<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold">My Grades</h2>
            <p class="text-gray-600 mt-1">View your grades across all enrolled courses</p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
