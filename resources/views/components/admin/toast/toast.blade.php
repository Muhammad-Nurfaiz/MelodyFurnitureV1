@props([
    'type' => 'success',
    'title' => '',
])
@php
$styles = [
    'success' => [
        'bg' => 'bg-green-50',
        'border' => 'border-green-200',
        'text' => 'text-green-700',
        'icon' => 'heroicon-o-check-circle',
    ],
    'danger' => [
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'text' => 'text-red-700',
        'icon' => 'heroicon-o-x-circle',
    ],
    'warning' => [
        'bg' => 'bg-yellow-50',
        'border' => 'border-yellow-200',
        'text' => 'text-yellow-700',
        'icon' => 'heroicon-o-exclamation-triangle',
    ],
    'info' => [
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-200',
        'text' => 'text-blue-700',
        'icon' => 'heroicon-o-information-circle',
    ],
];
@endphp
<div
    x-data="{ show:true }"
    x-init="setTimeout(() => show=false, 4000)"
    x-show="show"
    x-transition
    class="flex w-full items-start gap-3 rounded-xl border p-4 shadow-lg
           {{ $styles[$type]['bg'] }}
           {{ $styles[$type]['border'] }}
           {{ $styles[$type]['text'] }}"
>
    <x-dynamic-component
        :component="$styles[$type]['icon']"
        class="mt-0.5 h-6 w-6 shrink-0"
    />
    <div class="flex-1">
        @if($title)
            <h4 class="font-semibold">
                {{ $title }}
            </h4>
        @endif
        <div class="mt-1 text-sm">
            {{ $slot }}
        </div>
    </div>
    <button
        @click="show=false"
        class="rounded-lg p-1 hover:bg-black/5">
        <x-heroicon-o-x-mark class="h-5 w-5"/>
    </button>
</div>