@props(['status'])

@php
    $classes = match ($status) {
        App\Enums\TicketStatus::Open => 'bg-blue-100 text-blue-800',
        App\Enums\TicketStatus::InProgress => 'bg-amber-100 text-amber-800',
        App\Enums\TicketStatus::Resolved => 'bg-green-100 text-green-800',
        App\Enums\TicketStatus::Closed => 'bg-gray-200 text-gray-700',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', $classes]) }}>
    {{ $status->label() }}
</span>
