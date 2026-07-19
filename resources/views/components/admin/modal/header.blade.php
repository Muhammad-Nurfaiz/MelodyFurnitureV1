@props([
    'title' => null,
    'description' => null,
    'sticky' => true,
    'showClose' => true,
])

<div
    @class([
        'flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-5',
        'sticky top-0 z-10' => $sticky,
    ])>

    <div class="min-w-0 flex-1">

        @if($title)

            <h2 class="text-lg font-semibold leading-6 text-gray-900">

                {{ $title }}

            </h2>

        @elseif(trim($slot))

            <h2 class="text-lg font-semibold leading-6 text-gray-900">

                {{ $slot }}

            </h2>

        @endif

        @if($description)

            <p class="mt-1 text-sm text-gray-500">

                {{ $description }}

            </p>

        @endif

    </div>

    @if($showClose)

        <button
            type="button"
            x-on:click="$dispatch('modal-close-request')"
            class="
                inline-flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                text-gray-500
                transition
                hover:bg-gray-100
                hover:text-gray-700
                focus:outline-none
                focus:ring-2
                focus:ring-blue-500
                focus:ring-offset-2">

            <x-heroicon-o-x-mark class="h-5 w-5"/>

        </button>

    @endif

</div>