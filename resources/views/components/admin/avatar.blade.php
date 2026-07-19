@props([
    'src' => null,
    'alt' => '',
    'size' => 'md',
    'rounded' => 'xl',
])

@php

$sizes = [

    'xs' => 'h-8 w-8',

    'sm' => 'h-10 w-10',

    'md' => 'h-14 w-14',

    'lg' => 'h-20 w-20',

    'xl' => 'h-28 w-28',

];

$roundedClasses = [

    'sm' => 'rounded',

    'md' => 'rounded-md',

    'lg' => 'rounded-lg',

    'xl' => 'rounded-xl',

    '2xl' => 'rounded-2xl',

    'full' => 'rounded-full',

];

@endphp

<div
    {{ $attributes->merge([
        'class' => '
            overflow-hidden
            bg-gray-100
            border
            border-gray-200
            flex
            items-center
            justify-center
            shrink-0

            '.$sizes[$size].'

            '.$roundedClasses[$rounded]
    ]) }}>

    @if($src)

        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            loading="lazy"
            class="h-full w-full object-cover">

    @else

        <x-heroicon-o-photo
            class="h-6 w-6 text-gray-400"/>

    @endif

</div>