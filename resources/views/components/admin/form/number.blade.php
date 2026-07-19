@props([
    'min' => null,
    'max' => null,
    'step' => 1,
])

<input
    type="number"

    @if($min !== null)
        min="{{ $min }}"
    @endif

    @if($max !== null)
        max="{{ $max }}"
    @endif

    step="{{ $step }}"

    {{ $attributes->merge([
        'class' => '
            w-full
            rounded-xl
            border
            border-gray-300
            bg-white
            px-4
            py-3
            text-sm
            text-gray-900
            placeholder:text-gray-400
            transition
            focus:border-blue-500
            focus:ring-2
            focus:ring-blue-100
            disabled:bg-gray-100
            disabled:cursor-not-allowed'
    ]) }}
>