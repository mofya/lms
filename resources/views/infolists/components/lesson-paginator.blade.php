<div {{ $attributes }}>
    <div class="grid grid-cols-2 gap-4">
        @if($previous = $getCurrentLesson()->getPrevious())
            <x-filament::button
                href="{{ $this->getParentResource()::getUrl('lessons.view', [
                    $getCurrentLesson()->course,
                    $previous
                    ]) }}"
                tag="a"
                icon="heroicon-m-chevron-left"
            >
                {{ $previous->title }}
            </x-filament::button>
        @else
            <div class=""></div>
        @endif

        @if($next = $getCurrentLesson()->getNext())
            <x-filament::button
                wire:click="markAsCompletedAndGoToNext"
                icon="heroicon-m-chevron-right"
                icon-position="after"
            >
                {{ $next->title }}
            </x-filament::button>
        @endif
    </div>
</div>
