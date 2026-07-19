@props([
    'title' => null,
    'description' => null,
])

<div
    {{ $attributes->merge([
        'class' => 'flex flex-col gap-4 border-b border-gray-200 px-6 py-5 md:flex-row md:items-center md:justify-between'
    ]) }}>

    <div>

        @if($title)

            <h2 class="text-lg font-semibold text-gray-900">

                {{ $title }}

            </h2>

        @endif

        @if($description)

            <p class="mt-1 text-sm text-gray-500">

                {{ $description }}

            </p>

        @endif

    </div>

    @isset($actions)

        <div class="flex items-center gap-3">

            {{ $actions }}

        </div>

    @endisset

</div>