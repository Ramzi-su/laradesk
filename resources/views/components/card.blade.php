@props(['title' => null])

<section {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg p-4 sm:p-6']) }}>
    @if ($title)
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-4">{{ $title }}</h3>
    @endif
    {{ $slot }}
</section>
