@props([
    'variant' => 'gray',
    'style' => 'soft',
    'size' => 'md',
])

@php

$variants = [

    'primary' => [
        'soft' => 'bg-blue-100 text-blue-700',
        'solid' => 'bg-blue-600 text-white',
        'outline' => 'border border-blue-600 text-blue-600',
    ],

    'success' => [
        'soft' => 'bg-green-100 text-green-700',
        'solid' => 'bg-green-600 text-white',
        'outline' => 'border border-green-600 text-green-600',
    ],

    'danger' => [
        'soft' => 'bg-red-100 text-red-700',
        'solid' => 'bg-red-600 text-white',
        'outline' => 'border border-red-600 text-red-600',
    ],

    'warning' => [
        'soft' => 'bg-yellow-100 text-yellow-700',
        'solid' => 'bg-yellow-500 text-white',
        'outline' => 'border border-yellow-500 text-yellow-700',
    ],

    'info' => [
        'soft' => 'bg-sky-100 text-sky-700',
        'solid' => 'bg-sky-600 text-white',
        'outline' => 'border border-sky-600 text-sky-600',
    ],

    'purple' => [
        'soft' => 'bg-purple-100 text-purple-700',
        'solid' => 'bg-purple-600 text-white',
        'outline' => 'border border-purple-600 text-purple-600',
    ],

    'gray' => [
        'soft' => 'bg-gray-100 text-gray-700',
        'solid' => 'bg-gray-600 text-white',
        'outline' => 'border border-gray-400 text-gray-700',
    ],

];

$sizes = [

    'sm' => 'px-2 py-0.5 text-xs',

    'md' => 'px-2.5 py-1 text-xs',

];

@endphp

<span

    {{ $attributes->merge([

        'class' => '

            inline-flex

            items-center

            rounded-full

            font-medium

            whitespace-nowrap

            '.$sizes[$size].'

            '.$variants[$variant][$style]

    ]) }}

>

    {{ $slot }}

</span>