@props([
    'name' => null,
    'label' => null,
    'description' => null,
])

@php

    $hasError = $name && $errors->has($name);

@endphp

<label
    class="flex cursor-pointer items-start gap-3">

    <input

        type="checkbox"

        @if($name)
            name="{{ $name }}"
            id="{{ $name }}"
        @endif

        {{ $attributes
            ->except([
                'name',
                'label',
                'description',
            ])
            ->merge([
                'class' => '
                    mt-1
                    h-5
                    w-5
                    rounded
                    transition-all
                    duration-200

                    disabled:cursor-not-allowed
                    disabled:bg-gray-100

                    focus:ring-2

                    '.($hasError
                        ? 'border-red-400 text-red-600 focus:ring-red-100'
                        : 'border-gray-300 text-blue-600 focus:ring-blue-200'
                    )
            ]) }}
    >

    <div class="space-y-1">

        @if($label)

            <p class="text-sm font-medium text-gray-800">

                {{ $label }}

            </p>

        @endif

        @if($description)

            <p class="text-xs leading-5 text-gray-500">

                {{ $description }}

            </p>

        @endif

        @if($hasError)

            <p class="text-xs text-red-600">

                {{ $errors->first($name) }}

            </p>

        @endif

    </div>

</label>