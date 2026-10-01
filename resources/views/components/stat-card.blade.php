@props(['label', 'value', 'hint' => null])

<div {{ $attributes->merge(['class' => 'h-full bg-white shadow-sm sm:rounded-lg p-5']) }}>
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
    <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
