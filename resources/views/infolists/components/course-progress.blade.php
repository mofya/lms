<div {{ $attributes }}>
    <dt class="fi-in-entry-wrp-label inline-flex items-center gap-x-3">
        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
            {{ $getProgress() }} / {{ $getProgressMax() }} lessons finished ({{ $getPercentage() }}%)
        </span>
    </dt>
    <progress
        class="w-full rounded-full shadow-inner"
        id="progress"
        value="{{ $getProgress() }}"
        max="{{ $getProgressMax() }}"
    >
        {{ $getPercentage() }}%
    </progress>
</div>
