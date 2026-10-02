<x-app-layout>
    <x-slot name="title">{{ __('Categories') }}</x-slot>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Categories') }}</h2>
            <x-primary-link href="{{ route('admin.categories.create') }}">{{ __('New category') }}</x-primary-link>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold text-gray-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Name') }}</th>
                            <th class="px-4 py-3 hidden md:table-cell">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Tickets') }}</th>
                            <th class="px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                                <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ $category->description }}</td>
                                <td class="px-4 py-3 text-right">{{ $category->tickets_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-4">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-brand-700 hover:underline">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                              onsubmit="return confirm(@js(__('Delete this category?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-700 hover:underline">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-gray-500">{{ __('No category yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $categories->links() }}
        </div>
    </div>
</x-app-layout>
