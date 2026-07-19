@props([
    'title',
    'number',
    'current',
    'last' => false,
])

@php

$isCompleted = $number < $current;

$isCurrent = $number == $current;

@endphp

<div class="flex flex-1 items-center">

    <div class="flex items-center gap-3">

        <div
            class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-full
                text-sm
                font-semibold

                {{ $isCompleted ? 'bg-green-500 text-white' : '' }}
                {{ $isCurrent ? 'bg-blue-600 text-white' : '' }}
                {{ (!$isCompleted && !$isCurrent) ? 'bg-gray-200 text-gray-500' : '' }}
            ">

            @if($isCompleted)

                <x-heroicon-o-check class="h-5 w-5"/>

            @else

                {{ $number }}

            @endif

        </div>

        <div>

            <p class="text-sm font-semibold">

                {{ $title }}

            </p>

        </div>

    </div>

    @unless($last)

        <div class="mx-4 h-px flex-1 bg-gray-200"></div>

    @endunless

</div>