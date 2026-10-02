@props(['title' => null])

<section {{ $attributes->merge(['class' => 'bg-white border border-gray-200 rounded-xl p-4 sm:p-6']) }}>
    @if ($title)
        <h3 class="text-base font-bold text-ink mb-4">{{ $title }}</h3>
    @endif
    {{ $slot }}
</section>
