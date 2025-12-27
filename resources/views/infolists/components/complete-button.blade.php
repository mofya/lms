<div {{ $attributes }}>
    <x-filament::button
        wire:click="toggleCompleted"
    >
        {{ $getRecord()->isCompleted() ? 'Mark as uncomplete' : 'Mark as complete' }}
    </x-filament::button>
</div>
