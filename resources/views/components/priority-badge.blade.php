@props(['priority'])

@php
    $classes = match ($priority) {
        App\Enums\TicketPriority::Low => 'bg-gray-100 text-gray-700',
        App\Enums\TicketPriority::Medium => 'bg-sky-100 text-sky-800',
        App\Enums\TicketPriority::High => 'bg-orange-100 text-orange-800',
        App\Enums\TicketPriority::Urgent => 'bg-red-100 text-red-800',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', $classes]) }}>
    {{ $priority->label() }}
</span>
