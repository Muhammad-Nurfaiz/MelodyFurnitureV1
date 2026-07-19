@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,

    'icon' => null,
    'iconRight' => null,

    'loading' => null,

    'fullWidth' => false,
])

@php

$variants = [

    'primary'   => 'bg-blue-600 text-white hover:bg-blue-700',

    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',

    'outline'   => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',

    'danger'    => 'bg-red-600 text-white hover:bg-red-700',

    'success'   => 'bg-green-600 text-white hover:bg-green-700',

    'ghost'     => 'text-gray-700 hover:bg-gray-100',

];

$sizes = [

    'sm' => 'px-3 py-2 text-sm',

    'md' => 'px-4 py-2.5 text-sm',

    'lg' => 'px-5 py-3 text-base',

];

$classes = collect([

    'inline-flex',

    'items-center',

    'justify-center',

    'gap-2',

    'rounded-xl',

    'font-medium',

    'transition-all',

    'duration-200',

    'disabled:cursor-not-allowed',

    'disabled:opacity-50',

    $variants[$variant] ?? $variants['primary'],

    $sizes[$size] ?? $sizes['md'],

    $fullWidth ? 'w-full' : '',

])->implode(' ');

@endphp

@if($href)

<a
    href="{{ $href }}"
    {{ $attributes->except(['href','loading'])->merge([
        'class' => $classes
    ]) }}>

    @if($icon)

        <x-dynamic-component
            :component="'heroicon-o-'.$icon"
            class="h-5 w-5"/>

    @endif

    <span>

        {{ $slot }}

    </span>

    @if($iconRight)

        <x-dynamic-component
            :component="'heroicon-o-'.$iconRight"
            class="h-5 w-5"/>

    @endif

</a>

@else

<button

    type="{{ $type }}"

    @if($loading)
        x-bind:disabled="{{ $loading }}"
    @endif

    {{ $attributes->except('loading')->merge([
        'class' => $classes
    ]) }}>

    {{-- Loading --}}
    @if($loading)

        <template x-if="{{ $loading }}">

            <span class="inline-flex items-center gap-2">

                <svg
                    class="h-4 w-4 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24">

                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"/>

                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>

                </svg>

                <span>Memproses...</span>

            </span>

        </template>

        <template x-if="!{{ $loading }}">

            <span class="inline-flex items-center gap-2">

                @if($icon)

                    <x-dynamic-component
                        :component="'heroicon-o-'.$icon"
                        class="h-5 w-5"/>

                @endif

                <span>

                    {{ $slot }}

                </span>

                @if($iconRight)

                    <x-dynamic-component
                        :component="'heroicon-o-'.$iconRight"
                        class="h-5 w-5"/>

                @endif

            </span>

        </template>

    @else

        @if($icon)

            <x-dynamic-component
                :component="'heroicon-o-'.$icon"
                class="h-5 w-5"/>

        @endif

        <span>

            {{ $slot }}

        </span>

        @if($iconRight)

            <x-dynamic-component
                :component="'heroicon-o-'.$iconRight"
                class="h-5 w-5"/>

        @endif

    @endif

</button>

@endif