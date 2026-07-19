@props([
    'label' => null,
    'name' => null,
    'for' => null,
    'required' => false,
    'helper' => null,
])

@php

    $field = $name ?? $for;

@endphp

<div {{ $attributes->except([
        'label',
        'name',
        'for',
        'required',
        'helper',
    ])->merge([
        'class' => 'space-y-2'
    ]) }}>

    @if($label)

        <label
            @if($field)
                for="{{ $field }}"
            @endif
            class="block text-sm font-semibold text-gray-700">

            {{ $label }}

            @if($required)

                <span class="ml-0.5 text-red-500">*</span>

            @endif

        </label>

    @endif

    {{ $slot }}

    @if($helper)

        <p class="text-xs leading-5 text-gray-500">

            {{ $helper }}

        </p>

    @endif

    @if($field)

        @error($field)

            <p
                class="text-sm font-medium text-red-600">

                {{ $message }}

            </p>

        @enderror

    @endif

</div>