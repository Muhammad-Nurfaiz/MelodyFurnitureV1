@props([
    'name',
    'size' => 'lg',
    'closeOnOverlay' => true,
    'closeOnEscape' => true,
])

@php

$sizes = [

    'sm'   => 'max-w-md',
    'md'   => 'max-w-lg',
    'lg'   => 'max-w-2xl',
    'xl'   => 'max-w-4xl',
    '2xl'  => 'max-w-6xl',
    'full' => 'max-w-[95vw]',

];

@endphp

<div

    x-data="{

        open:false,

        show(name){

            if(name !== '{{ $name }}') return;

            this.open = true;

            document.body.classList.add('overflow-hidden');

        },

        hide(name){

            if(name !== '{{ $name }}') return;

            this.open = false;

            document.body.classList.remove('overflow-hidden');

        }

    }"

    x-cloak

    x-on:open-modal.window="show($event.detail)"

    x-on:close-modal.window="hide($event.detail)"

    x-on:keydown.escape.window="
        if(open && {{ $closeOnEscape ? 'true' : 'false' }}){
            hide('{{ $name }}')
        }
    "

>

    {{-- Overlay --}}
    <div

        x-show="open"

        x-transition.opacity.duration.200ms

        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px]"

        @click="
            if({{ $closeOnOverlay ? 'true' : 'false' }}){
                hide('{{ $name }}')
            }
        "

    ></div>

    {{-- Modal Container --}}
    <div

        x-show="open"

        x-transition:enter="transition ease-out duration-200"

        x-transition:enter-start="opacity-0 scale-95 translate-y-4"

        x-transition:enter-end="opacity-100 scale-100 translate-y-0"

        x-transition:leave="transition ease-in duration-150"

        x-transition:leave-start="opacity-100 scale-100 translate-y-0"

        x-transition:leave-end="opacity-0 scale-95 translate-y-4"

        class="fixed inset-0 z-50 overflow-y-auto"

    >

        <div

            class="flex min-h-full items-center justify-center p-4 md:p-6"

        >

            <div

                @click.stop

                class="flex w-full flex-col rounded-2xl bg-white shadow-2xl {{ $sizes[$size] }}"

            >

                {{ $slot }}

            </div>

        </div>

    </div>

</div>