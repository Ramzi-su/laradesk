<x-app-layout>
    <x-slot name="title">{{ __('New ticket') }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New ticket') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- enctype is required for file uploads. --}}
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf

                <div>
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required maxlength="255" autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="category_id" :value="__('Category')" />
                        <x-select-input id="category_id" name="category_id" class="mt-1 block w-full" required>
                            <option value="">{{ __('Choose a category') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="priority" :value="__('Priority')" />
                        <x-select-input id="priority" name="priority" class="mt-1 block w-full" required>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', App\Enums\TicketPriority::Medium->value) === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <x-textarea-input id="description" name="description" rows="8" class="mt-1 block w-full" required>{{ old('description') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="attachments" :value="__('Attachments')" />
                    <input id="attachments" name="attachments[]" type="file" multiple
                           accept=".{{ implode(',.', App\Http\Requests\StoreTicketRequest::ALLOWED_EXTENSIONS) }}"
                           class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200" />
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Up to :count files: :types, :size MB max each.', [
                            'count' => App\Http\Requests\StoreTicketRequest::MAX_FILES,
                            'types' => implode(', ', App\Http\Requests\StoreTicketRequest::ALLOWED_EXTENSIONS),
                            'size' => App\Http\Requests\StoreTicketRequest::MAX_FILE_KILOBYTES / 1024,
                        ]) }}
                    </p>
                    <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
                    <x-input-error :messages="collect($errors->get('attachments.*'))->flatten()->all()" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('tickets.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Create ticket') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
