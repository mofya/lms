<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @php
        $result = $getRecord()->progress();
        $progress = $result['value'];
        $progressMax = $result['max'];
        $percentage = $result['percentage'];
    @endphp

    <div class="flex flex-col text-xs gap-1">
        <div>{{ $progress }} / {{ $progressMax }} lessons finished ({{ $percentage }}%)</div>
        <progress
            class="w-full rounded-full shadow-inner"
            id="progress"
            value="{{ $progress }}"
            max="{{ $progressMax }}"
        >
            {{ $percentage }}%
        </progress>
    </div>
</div>
