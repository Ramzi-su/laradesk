<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2 bg-red-700 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-red-800 active:bg-red-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition-colors']) }}>
    {{ $slot }}
</button>
