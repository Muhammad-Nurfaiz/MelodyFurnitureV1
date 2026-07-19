@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'color' => 'blue',
])
@php
$colors = [
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'yellow' => 'bg-yellow-100 text-yellow-600',
    'red' => 'bg-red-100 text-red-600',
    'purple' => 'bg-purple-100 text-purple-600',
    'gray' => 'bg-gray-100 text-gray-600',
];
@endphp
<div
    {{ $attributes->merge([
        'class' => '
            rounded-2xl
            border
            border-gray-200
            bg-white
            p-6
            shadow-sm
            transition
            duration-200
            hover:shadow-md'
    ]) }}
>
    <div class="flex items-start justify-between">
        <div>
            <p
                class="text-sm font-medium text-gray-500">
                {{ $title }}
            </p>
            <h2
                class="mt-2 text-3xl font-bold text-gray-900">
                {{ $value }}
            </h2>
            @if($description)
                <p
                    class="mt-3 text-sm text-gray-500">
                    {{ $description }}
                </p>
            @endif
        </div>
        @if($icon)
            <div
                class="flex h-12 w-12 items-center justify-center rounded-xl {{ $colors[$color] }}">
                <x-dynamic-component
                    :component="'heroicon-o-'.$icon"
                    class="h-6 w-6"/>
            </div>
        @endif
    </div>
</div>