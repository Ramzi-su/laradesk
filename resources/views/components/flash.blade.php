@foreach (['success' => 'bg-green-50 text-green-800 border-green-200', 'error' => 'bg-red-50 text-red-800 border-red-200'] as $type => $classes)
    @if (session($type))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div x-data="{ show: true }" x-show="show" role="{{ $type === 'error' ? 'alert' : 'status' }}"
                 class="flex items-start justify-between gap-4 rounded-md border px-4 py-3 text-sm {{ $classes }}">
                <p>{{ session($type) }}</p>
                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="{{ __('Close') }}">&times;</button>
            </div>
        </div>
    @endif
@endforeach
