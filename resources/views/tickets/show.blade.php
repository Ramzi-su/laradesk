<x-app-layout>
    <x-slot name="title">{{ $ticket->reference }}</x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- The ticket itself, drawn as a paper ticket with a detachable stub. --}}
            {{-- overflow-hidden clips the outer half of the notches, leaving a clean cut. --}}
            <section class="lg:col-span-3 flex flex-col sm:flex-row overflow-hidden bg-white border border-gray-200 rounded-xl">
                <div class="flex-1 p-6">
                    <h1 class="text-2xl font-bold leading-tight text-ink">{{ $ticket->title }}</h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __(':category, opened :date by :client', [
                            'category' => $ticket->category->name,
                            'date' => $ticket->created_at->isoFormat('LL'),
                            'client' => $ticket->client->name,
                        ]) }}
                    </p>
                </div>
                <div class="ticket-stub flex flex-row items-center justify-between gap-3 p-6 sm:w-60 sm:flex-col sm:items-start sm:justify-center">
                    <p class="text-2xl font-bold tracking-wide tabular-nums text-brand-800">{{ $ticket->reference }}</p>
                    <div class="flex flex-wrap gap-2">
                        <x-status-badge :status="$ticket->status" />
                        <x-priority-badge :priority="$ticket->priority" />
                    </div>
                </div>
            </section>

            <div class="space-y-6 lg:col-span-2">
                <x-card :title="__('Description')">
                    {{-- whitespace-pre-line keeps line breaks while {{ }} escapes the content (no XSS). --}}
                    <p class="whitespace-pre-line text-gray-800">{{ $ticket->description }}</p>

                    @can('update', $ticket)
                        <div class="mt-4 text-right">
                            <a href="{{ route('tickets.edit', $ticket) }}" class="text-sm text-brand-700 hover:underline">{{ __('Edit') }}</a>
                        </div>
                    @endcan
                </x-card>

                @if ($ticket->attachments->isNotEmpty())
                    <x-card :title="__('Attachments')">
                        <ul class="divide-y divide-gray-100">
                            @foreach ($ticket->attachments as $attachment)
                                <li class="flex items-center justify-between gap-4 py-2 text-sm">
                                    <a href="{{ route('attachments.show', $attachment) }}" class="font-medium text-brand-700 hover:underline break-all">
                                        {{ $attachment->original_name }}
                                    </a>
                                    <span class="whitespace-nowrap text-gray-500">
                                        {{ Illuminate\Support\Number::fileSize($attachment->size, precision: 1) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                <x-card :title="__('Comments')" id="comments">
                    <ol class="space-y-4">
                        @forelse ($ticket->comments as $comment)
                            <li @class([
                                'rounded-md border p-4',
                                'border-amber-200 bg-amber-50' => $comment->is_internal,
                                'border-gray-100' => ! $comment->is_internal,
                            ])>
                                <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="font-medium text-gray-900">{{ $comment->user->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $comment->user->role->label() }}</span>
                                    @if ($comment->is_internal)
                                        <span class="rounded bg-amber-200 px-1.5 py-0.5 text-xs font-medium text-amber-900">{{ __('Internal note') }}</span>
                                    @endif
                                    <span class="ms-auto text-xs text-gray-500" title="{{ $comment->created_at->isoFormat('LLL') }}">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="whitespace-pre-line text-sm text-gray-800">{{ $comment->body }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">{{ __('No comment yet.') }}</li>
                        @endforelse
                    </ol>

                    @can('comment', $ticket)
                        <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="mt-6 space-y-3">
                            <x-input-label for="body" :value="__('Add a comment')" />
                            <x-textarea-input id="body" name="body" rows="4" class="block w-full" required>{{ old('body') }}</x-textarea-input>
                            <x-input-error :messages="$errors->get('body')" />
                            <div class="flex items-center justify-between gap-4">
                                @can('commentInternally', $ticket)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal'))
                                               class="rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500">
                                        {{ __('Internal note (hidden from the client)') }}
                                    </label>
                                @else
                                    <span></span>
                                @endcan
                                <x-primary-button>{{ __('Send') }}</x-primary-button>
                            </div>

                            @csrf
                        </form>
                    @endcan
                </x-card>
            </div>

            <aside class="space-y-6">
                <x-card :title="__('Details')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Agent') }}</dt><dd class="text-right">{{ $ticket->agent?->name ?? __('Unassigned') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Created') }}</dt><dd class="text-right">{{ $ticket->created_at->isoFormat('LLL') }}</dd></div>
                        @if ($ticket->resolved_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Resolved') }}</dt><dd class="text-right">{{ $ticket->resolved_at->isoFormat('LLL') }}</dd></div>
                        @endif
                        @if ($ticket->closed_at)
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Closed') }}</dt><dd class="text-right">{{ $ticket->closed_at->isoFormat('LLL') }}</dd></div>
                        @endif
                    </dl>
                </x-card>

                @can('changeStatus', $ticket)
                    <x-card :title="__('Status')">
                        <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            @if (auth()->user()->isClient())
                                {{-- A client can only close their resolved ticket. --}}
                                <input type="hidden" name="status" value="{{ App\Enums\TicketStatus::Closed->value }}">
                                <p class="flex-1 text-sm text-gray-600">{{ __('Is your problem solved?') }}</p>
                                <x-primary-button>{{ __('Close the ticket') }}</x-primary-button>
                            @else
                                <x-select-input name="status" class="flex-1" aria-label="{{ __('Status') }}">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ $status->label() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-primary-button>{{ __('Change') }}</x-primary-button>
                            @endif
                        </form>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </x-card>
                @endcan

                @can('changePriority', $ticket)
                    <x-card :title="__('Priority')">
                        <form method="POST" action="{{ route('tickets.priority', $ticket) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <x-select-input name="priority" class="flex-1" aria-label="{{ __('Priority') }}">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ $priority->label() }}</option>
                                @endforeach
                            </x-select-input>
                            <x-primary-button>{{ __('Change') }}</x-primary-button>
                        </form>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </x-card>
                @endcan

                @can('assign', $ticket)
                    <x-card :title="__('Assignment')">
                        <form method="POST" action="{{ route('tickets.assignment', $ticket) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            @if (auth()->user()->isAdmin())
                                <x-select-input name="agent_id" class="flex-1" aria-label="{{ __('Agent') }}">
                                    <option value="">{{ __('Unassigned') }}</option>
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($ticket->agent_id === $agent->id)>{{ $agent->name }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-primary-button>{{ __('Assign') }}</x-primary-button>
                            @else
                                <input type="hidden" name="agent_id" value="{{ auth()->id() }}">
                                <x-primary-button class="w-full justify-center">{{ __('Take this ticket') }}</x-primary-button>
                            @endif
                        </form>
                        <x-input-error :messages="$errors->get('agent_id')" class="mt-2" />
                    </x-card>
                @endcan

                @can('delete', $ticket)
                    <x-card>
                        <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                              onsubmit="return confirm(@js(__('Delete this ticket?')))">
                            @csrf
                            @method('DELETE')
                            <x-danger-button class="w-full justify-center">{{ __('Delete the ticket') }}</x-danger-button>
                        </form>
                    </x-card>
                @endcan
            </aside>
        </div>
    </div>
</x-app-layout>
