<x-app-layout>
    <x-slot name="title">{{ __('Dashboard') }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
    </x-slot>

    @push('scripts')
        @vite('resources/js/dashboard.js')
    @endpush

    @php
        $total = array_sum($byStatus);
        $priorityBars = [
            'low' => 'bg-gray-400',
            'medium' => 'bg-sky-500',
            'high' => 'bg-orange-500',
            'urgent' => 'bg-red-600',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                <x-stat-card :label="__('Total tickets')" :value="$total" />
                @foreach ($statuses as $status)
                    <a href="{{ route('tickets.index', ['status' => $status->value]) }}" class="block hover:opacity-80">
                        <x-stat-card :label="$status->label()" :value="$byStatus[$status->value]" />
                    </a>
                @endforeach
                <x-stat-card :label="__('Average resolution time')"
                             :value="$averageResolutionHours === null ? '—' : __(':hours h', ['hours' => Illuminate\Support\Number::format($averageResolutionHours, 1)])"
                             :hint="__('From creation to resolution')" />
            </div>

            <x-card :title="__('Tickets created per day (last 30 days)')">
                <div class="h-72">
                    {{-- {{ }} escapes the JSON for the HTML attribute; dashboard.js parses it back. --}}
                    <canvas id="tickets-per-day" data-chart="{{ json_encode($chart) }}"
                            role="img" aria-label="{{ __('Tickets created per day (last 30 days)') }}"></canvas>
                </div>
            </x-card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-card :title="__('Tickets by priority')">
                    <ul class="space-y-4">
                        @foreach ($priorities as $priority)
                            @php($count = $byPriority[$priority->value])
                            <li>
                                <div class="mb-1 flex justify-between text-sm">
                                    <a href="{{ route('tickets.index', ['priority' => $priority->value]) }}" class="hover:underline">
                                        <x-priority-badge :priority="$priority" />
                                    </a>
                                    <span class="tabular-nums text-gray-600">{{ $count }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full {{ $priorityBars[$priority->value] }}"
                                         style="width: {{ $total > 0 ? round($count / $total * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>

                <x-card :title="__('Tickets to handle per agent')">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($agents as $agent)
                                <tr>
                                    <td class="py-2">
                                        <a href="{{ route('tickets.index', ['agent' => $agent->id]) }}" class="hover:underline">{{ $agent->name }}</a>
                                    </td>
                                    <td class="py-2 text-right tabular-nums">{{ $agent->open_tickets_count }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="py-2 italic text-gray-500">{{ __('Unassigned') }}</td>
                                <td class="py-2 text-right tabular-nums text-gray-500">{{ $unassigned }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-500">{{ __('Open and in progress tickets.') }}</p>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
