@props([
    'title' => 'Belum ada data',
    'description' => 'Data akan muncul di sini.',
    'icon' => 'archive-box',
])

<div
    {{ $attributes->merge([
        'class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-8 py-16 text-center'
    ]) }}>

    {{-- Icon --}}
    <div
        class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-white shadow-sm">

        <x-dynamic-component
            :component="'heroicon-o-'.$icon"
            class="h-10 w-10 text-gray-400"/>

    </div>

    {{-- Title --}}
    <h3
        class="text-lg font-semibold text-gray-900">

        {{ $title }}

    </h3>

    {{-- Description --}}
    <p
        class="mt-2 max-w-md text-sm leading-6 text-gray-500">

        {{ $description }}

    </p>

    {{-- Action --}}
    @isset($action)

        <div
            class="mt-8">

            {{ $action }}

        </div>

    @endisset

</div>