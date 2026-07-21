@props([
    'name',
    'maxWidth' => 'md',
])

@php

$widths = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
];

@endphp

<div
    x-data="{ 
    open:false,
    alert:{
            title:'',
            message:'',
            action:''
        }
    }"
    x-cloak

    x-on:open-alert.window="
        if($event.detail.name === '{{ $name }}'){
            alert = $event.detail;
            open = true;
        }
    "

    x-on:close-alert.window="
        if($event.detail === '{{ $name }}'){
            open = false;
        }
    "

    x-on:keydown.escape.window="
        $dispatch('close-alert','{{ $name }}')
    "
>

    {{-- Overlay --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-black/40"
        @click="$dispatch('close-alert','{{ $name }}')">
    </div>

    {{-- Dialog --}}
    <div
        x-show="open"
        x-transition
        class="fixed inset-0 z-50 flex items-center justify-center p-6">

        <div
            @click.stop
            class="w-full {{ $widths[$maxWidth] }} rounded-2xl bg-white shadow-2xl">

            {{-- Header --}}
            <div class="flex items-start gap-4 border-b border-gray-100 p-6">

                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100">

                    <x-heroicon-o-exclamation-triangle
                        class="h-6 w-6 text-red-600"/>

                </div>

                <div class="flex-1">

                    <h3
                        class="text-lg font-semibold text-gray-900"
                        x-text="alert.title">
                    </h3>

                    <p
                        class="mt-1 text-sm text-gray-500"
                        x-text="alert.message">
                    </p>

                </div>

            </div>

            {{-- Footer --}}
            <div
                class="flex justify-end gap-3 p-6">

                <x-admin.button
                    variant="secondary"
                    @click="$dispatch('close-alert','{{ $name }}')">

                    Batal

                </x-admin.button>

                {{ $slot }}

            </div>

        </div>

    </div>

</div>