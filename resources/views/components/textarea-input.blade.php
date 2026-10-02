@props(['disabled' => false])

<textarea @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm']) }}>{{ $slot }}</textarea>
