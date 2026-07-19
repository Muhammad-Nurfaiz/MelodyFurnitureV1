@props([
    'padding' => 'md',
    'scrollable' => true,
])

@php

$paddings = [

    'sm' => 'p-4',

    'md' => 'px-6 py-5',

    'lg' => 'p-8',

];

@endphp

<div
    @class([

        'flex-1',

        $paddings[$padding] ?? $paddings['md'],

        'overflow-y-auto max-h-[calc(100vh-12rem)]' => $scrollable,

    ])>

    {{ $slot }}

</div>