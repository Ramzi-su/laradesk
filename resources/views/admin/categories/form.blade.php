<x-app-layout>
    @php($editing = $category->exists)

    <x-slot name="title">{{ $editing ? $category->name : __('New category') }}</x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $editing ? __('Edit category :name', ['name' => $category->name]) : __('New category') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
                  class="bg-white border border-gray-200 rounded-xl p-6 space-y-6">
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $category->name)" required maxlength="100" autofocus />
                    <x-input-error :messages="[...$errors->get('name'), ...$errors->get('slug')]" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" :value="__('Description')" />
                    <x-textarea-input id="description" name="description" rows="3" class="mt-1 block w-full" maxlength="500">{{ old('description', $category->description) }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.categories.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </div>

                @csrf
                @if ($editing)
                    @method('PUT')
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
