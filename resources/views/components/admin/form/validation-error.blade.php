@props([
    'name' => null,
    'message' => null,
    'icon' => true,
])

@php

    $errorMessage = $message;

    if (!$errorMessage && $name) {

        $errorMessage = $errors->first($name);

    }

@endphp

@if($errorMessage)

    <div
        {{ $attributes->merge([
            'class' => 'mt-1 flex items-start gap-2 text-sm text-red-600'
        ]) }}>

        @if($icon)

            <x-heroicon-o-exclamation-circle
                class="mt-0.5 h-4 w-4 flex-shrink-0"/>

        @endif

        <span>

            {{ $errorMessage }}

        </span>

    </div>

@endif