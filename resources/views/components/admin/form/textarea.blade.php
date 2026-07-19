@props([
    'rows' => 5,
    'name' => null,
])

@php

    $hasError = $name && $errors->has($name);

@endphp

<textarea
    rows="{{ $rows }}"

    @if($name)
        name="{{ $name }}"
        id="{{ $name }}"
    @endif

    {{ $attributes
        ->except([
            'rows',
            'name',
        ])
        ->merge([
            'class' => '
                block
                w-full
                rounded-xl
                border
                bg-white
                px-4
                py-3
                text-sm
                text-gray-900
                placeholder:text-gray-400

                resize-y

                transition-all
                duration-200

                disabled:cursor-not-allowed
                disabled:bg-gray-100

                focus:outline-none
                focus:ring-2

                '.($hasError
                    ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                    : 'border-gray-300 focus:border-blue-500 focus:ring-blue-100'
                )
        ]) }}
>{{ $slot }}</textarea>