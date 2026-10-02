<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LaraDesk') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=atkinson-hyperlegible:400,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-10 sm:pt-0 bg-paper">
            <a href="/" class="flex flex-col items-center gap-2 text-center">
                <x-brand size="lg" />
                <span class="text-sm text-gray-600">{{ __('Customer support platform') }}</span>
            </a>

            <div class="w-full sm:max-w-md mt-8 px-6 py-6 bg-white border border-gray-200 shadow-sm overflow-hidden rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
