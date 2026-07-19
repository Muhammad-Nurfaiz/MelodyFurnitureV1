@props([

    'title',

    'description' => null,

])

<div
    {{ $attributes->merge([
        'class' => 'mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between'
    ]) }}>

    {{-- Left --}}
    <div>

        <h1
            class="text-3xl font-bold tracking-tight text-gray-900">

            {{ $title }}

        </h1>

        @if($description)

            <p
                class="mt-2 text-sm text-gray-500">

                {{ $description }}

            </p>

        @endif

    </div>

    {{-- Right --}}
    @if(isset($actions))

        <div
            class="flex items-center gap-3">

            {{ $actions }}

        </div>

    @endif

</div>