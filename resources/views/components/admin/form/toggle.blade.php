@props([
    'name' => null,
    'label' => null,
    'description' => null,
])

@php

    $hasError = $name && $errors->has($name);

@endphp

<label
    class="flex cursor-pointer items-start justify-between gap-4">

    <div class="flex-1">

        @if($label)

            <p class="text-sm font-medium text-gray-800">

                {{ $label }}

            </p>

        @endif

        @if($description)

            <p class="mt-1 text-xs leading-5 text-gray-500">

                {{ $description }}

            </p>

        @endif

        @if($hasError)

            <p class="mt-2 text-xs text-red-600">

                {{ $errors->first($name) }}

            </p>

        @endif

    </div>

    <div class="relative flex-shrink-0">

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
                    'class' => 'peer sr-only'
                ]) }}
        >

        {{-- Track --}}
        <div

            class="
                h-6
                w-11
                rounded-full
                transition-all
                duration-200

                peer-focus:ring-4

                peer-disabled:cursor-not-allowed
                peer-disabled:opacity-50

                {{ $hasError
                    ? 'bg-red-200 peer-focus:ring-red-100 peer-checked:bg-red-500'
                    : 'bg-gray-300 peer-focus:ring-blue-200 peer-checked:bg-blue-600'
                }}
            ">

        </div>

        {{-- Thumb --}}
        <div

            class="
                absolute
                left-0.5
                top-0.5

                h-5
                w-5

                rounded-full
                bg-white
                shadow

                transition-all
                duration-200

                peer-checked:translate-x-5
            ">

        </div>

    </div>

</label>