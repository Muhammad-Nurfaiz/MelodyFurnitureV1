@props([
    'name' => null,
    'placeholder' => 'Pilih data',
    'required' => false,
])

@php

$hasError = $name && $errors->has($name);

@endphp

<div class="relative">

    <select

        @if($name)
            name="{{ $name }}"
            id="{{ $name }}"
        @endif

        @if($required)
            required
        @endif

        {{ $attributes
            ->except([
                'name',
                'placeholder',
                'required',
            ])
            ->merge([
                'class' => '

                    appearance-none

                    block
                    w-full

                    rounded-xl

                    border

                    bg-white

                    px-4
                    py-2.5
                    pr-10

                    text-sm
                    text-gray-900

                    transition-all
                    duration-200

                    disabled:cursor-not-allowed
                    disabled:bg-gray-100

                    focus:outline-none
                    focus:ring-2

                    '.(
                        $hasError
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-100'
                    )

            ]) }}

    >

        @if($placeholder)

            <option
                value=""
                @if($required) disabled @endif>

                {{ $placeholder }}

            </option>

        @endif

        {{ $slot }}

    </select>

    <div
        class="pointer-events-none absolute inset-y-0 right-3 flex items-center">

        <x-heroicon-o-chevron-down
            class="h-4 w-4 text-gray-400"/>

    </div>

</div>