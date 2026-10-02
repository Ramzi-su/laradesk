<x-app-layout>
    <x-slot name="title">{{ $ticket->reference }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit ticket :reference', ['reference' => $ticket->reference]) }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="bg-white border border-gray-200 rounded-xl p-6 space-y-6">
                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $ticket->title)" required maxlength="255" autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <x-textarea-input id="description" name="description" rows="8" class="mt-1 block w-full" required>{{ old('description', $ticket->description) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('tickets.show', $ticket) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>

                @csrf
                @method('PUT')
            </form>
        </div>
    </div>
</x-app-layout>
