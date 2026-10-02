<x-app-layout>
    <x-slot name="title">{{ __('Users') }}</x-slot>

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Users') }}</h2>
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-2">
                <x-select-input name="role" aria-label="{{ __('Role') }}" onchange="this.form.submit()">
                    <option value="">{{ __('All roles') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </x-select-input>
                <noscript><x-primary-button>{{ __('Filter') }}</x-primary-button></noscript>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold text-gray-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('Name') }}</th>
                            <th class="px-4 py-3 hidden md:table-cell">{{ __('Email') }}</th>
                            <th class="px-4 py-3 text-right hidden sm:table-cell">{{ __('Opened tickets') }}</th>
                            <th class="px-4 py-3 text-right hidden sm:table-cell">{{ __('Assigned tickets') }}</th>
                            <th class="px-4 py-3">{{ __('Role') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                                <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-right hidden sm:table-cell">{{ $user->tickets_count }}</td>
                                <td class="px-4 py-3 text-right hidden sm:table-cell">{{ $user->assigned_tickets_count }}</td>
                                <td class="px-4 py-3">
                                    @can('updateRole', $user)
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <x-select-input name="role" class="text-sm py-1" aria-label="{{ __('Role of :name', ['name' => $user->name]) }}">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                                @endforeach
                                            </x-select-input>
                                            <x-secondary-button type="submit" class="py-1">{{ __('Save') }}</x-secondary-button>
                                        </form>
                                    @else
                                        {{ $user->role->label() }} <span class="text-xs text-gray-400">({{ __('you') }})</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
