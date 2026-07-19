@props([
    'align' => 'end',
    'sticky' => false,
    'padding' => 'md',
])

@php

$alignments = [

    'start'   => 'justify-start',

    'center'  => 'justify-center',

    'between' => 'justify-between',

    'end'     => 'justify-end',

];

$paddings = [

    'sm' => 'px-4 py-3',

    'md' => 'px-6 py-4',

    'lg' => 'px-8 py-5',

];

@endphp

<div
    @class([

        'flex items-center gap-3 border-t border-gray-200 bg-white',

        $alignments[$align] ?? $alignments['end'],

        $paddings[$padding] ?? $paddings['md'],

        'sticky bottom-0 z-10' => $sticky,

    ])>

    {{ $slot }}

</div>