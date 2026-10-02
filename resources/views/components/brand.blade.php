@props(['size' => 'md'])

{{-- Logo + wordmark, used in the navigation and on the guest pages. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <x-application-logo :class="$size === 'lg' ? 'h-11 w-auto' : 'h-7 w-auto'" />
    <span @class(['font-bold tracking-tight text-ink', 'text-lg' => $size === 'md', 'text-3xl' => $size === 'lg'])>LaraDesk</span>
</span>
