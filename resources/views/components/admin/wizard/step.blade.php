@props([
    'number',
    'title' => null,
    'description' => null,
    'transition' => true,
])

<div
    x-show="isStep({{ $number }})"

    @if($transition)
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"

        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
    @endif

    x-cloak

    {{ $attributes }}
>

    @if($title || $description)

        <div class="mb-6">

            @if($title)

                <h2 class="text-2xl font-bold text-gray-900">

                    {{ $title }}

                </h2>

            @endif

            @if($description)

                <p class="mt-2 text-sm text-gray-500">

                    {{ $description }}

                </p>

            @endif

        </div>

    @endif

    {{ $slot }}

</div>