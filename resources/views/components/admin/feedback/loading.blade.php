@props([
    'text' => 'Memuat data...',
    'size' => 'md',
])
@php
$sizes = [
    'sm' => 'h-4 w-4',
    'md' => 'h-6 w-6',
    'lg' => 'h-10 w-10',
];
@endphp
<div
    {{ $attributes->merge([
        'class' => 'flex flex-col items-center justify-center gap-4 py-8'
    ]) }}>
    <svg
        class="animate-spin text-blue-600 {{ $sizes[$size] }}"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24">
        <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"/>
        <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
    </svg>
    <p class="text-sm text-gray-500">
        {{ $text }}
    </p>
</div>