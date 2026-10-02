<x-app-layout>
    <x-slot name="title">{{ __('Tickets') }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tickets') }}</h2>
            @can('create', App\Models\Ticket::class)
                <x-primary-link href="{{ route('tickets.create') }}">{{ __('New ticket') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- GET form: the filters stay in the URL, so a filtered list can be bookmarked or shared. --}}
            <form method="GET" action="{{ route('tickets.index') }}" class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
                <div class="sm:col-span-2 lg:col-span-4">
                    <x-input-label for="search" :value="__('Search')" />
                    <x-text-input id="search" name="search" type="search" class="mt-1 block w-full"
                                  :value="request('search')" :placeholder="__('Reference, title or description')" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <x-select-input id="status" name="status" class="mt-1 block w-full">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="priority" :value="__('Priority')" />
                    <x-select-input id="priority" name="priority" class="mt-1 block w-full">
                        <option value="">{{ __('All priorities') }}</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="category" :value="__('Category')" />
                    <x-select-input id="category" name="category" class="mt-1 block w-full">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                @if ($agents->isNotEmpty())
                    <div>
                        <x-input-label for="agent" :value="__('Agent')" />
                        <x-select-input id="agent" name="agent" class="mt-1 block w-full">
                            <option value="">{{ __('All agents') }}</option>
                            @foreach ($agents as $agent)
                                <option value="{{ $agent->id }}" @selected((int) request('agent') === $agent->id)>{{ $agent->name }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                @endif
                <div class="flex gap-2 sm:col-span-2 lg:col-span-4 justify-end">
                    <a href="{{ route('tickets.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                </div>
            </form>

            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                @if ($tickets->isEmpty())
                    <p class="p-6 text-gray-500">{{ __('No ticket found.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-bold text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                                    <th class="px-4 py-3">{{ __('Title') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Priority') }}</th>
                                    <th class="px-4 py-3 hidden md:table-cell">{{ __('Category') }}</th>
                                    @unless (auth()->user()->isClient())
                                        <th class="px-4 py-3 hidden lg:table-cell">{{ __('Client') }}</th>
                                    @endunless
                                    <th class="px-4 py-3 hidden lg:table-cell">{{ __('Agent') }}</th>
                                    <th class="px-4 py-3 hidden sm:table-cell">{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-bold tabular-nums text-gray-600 whitespace-nowrap">{{ $ticket->reference }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-brand-700 hover:underline">{{ $ticket->title }}</a>
                                        </td>
                                        <td class="px-4 py-3"><x-status-badge :status="$ticket->status" /></td>
                                        <td class="px-4 py-3"><x-priority-badge :priority="$ticket->priority" /></td>
                                        <td class="px-4 py-3 hidden md:table-cell">{{ $ticket->category->name }}</td>
                                        @unless (auth()->user()->isClient())
                                            <td class="px-4 py-3 hidden lg:table-cell">{{ $ticket->client->name }}</td>
                                        @endunless
                                        <td class="px-4 py-3 hidden lg:table-cell">{{ $ticket->agent?->name ?? __('Unassigned') }}</td>
                                        <td class="px-4 py-3 hidden sm:table-cell whitespace-nowrap text-gray-500" title="{{ $ticket->created_at->isoFormat('LLL') }}">
                                            {{ $ticket->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{ $tickets->links() }}
        </div>
    </div>
</x-app-layout>
