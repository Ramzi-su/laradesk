<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-brand-700 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-brand-800 active:bg-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:opacity-50 transition-colors']) }}>
    {{ $slot }}
</button>
