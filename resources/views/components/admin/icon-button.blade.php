@props([
    'icon',
    'color' => 'gray',
    'href' => null,
])

@php

$colors = [

    'gray' => 'text-gray-600 hover:bg-gray-100',

    'blue' => 'text-blue-600 hover:bg-blue-50',

    'red' => 'text-red-600 hover:bg-red-50',

    'green' => 'text-green-600 hover:bg-green-50',

];

$classes = $colors[$color] ?? $colors['gray'];

@endphp

@if($href)

<a

    href="{{ $href }}"

    {{ $attributes->merge([
        'class' => "inline-flex h-9 w-9 items-center justify-center rounded-lg transition {$classes}"
    ]) }}>

    <x-dynamic-component
        :component="'heroicon-o-'.$icon"
        class="h-5 w-5"/>

</a>

@else

<button

    type="button"

    {{ $attributes->merge([
        'class' => "inline-flex h-9 w-9 items-center justify-center rounded-lg transition {$classes}"
    ]) }}>

    <x-dynamic-component
        :component="'heroicon-o-'.$icon"
        class="h-5 w-5"/>

</button>

@endif