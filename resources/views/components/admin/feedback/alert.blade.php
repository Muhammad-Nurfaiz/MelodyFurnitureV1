@props([
    'variant' => 'success'
])
@php
$styles = [
    'success' => [
        'wrapper' => 'border-green-200 bg-green-50',
        'icon' => 'check-circle',
        'iconColor' => 'text-green-600',
        'title' => 'text-green-900',
        'body' => 'text-green-700',
    ],
    'danger' => [
        'wrapper' => 'border-red-200 bg-red-50',
        'icon' => 'x-circle',
        'iconColor' => 'text-red-600',
        'title' => 'text-red-900',
        'body' => 'text-red-700',
    ],
    'warning' => [
        'wrapper' => 'border-amber-200 bg-amber-50',
        'icon' => 'exclamation-triangle',
        'iconColor' => 'text-amber-600',
        'title' => 'text-amber-900',
        'body' => 'text-amber-700',
    ],
    'info' => [
        'wrapper' => 'border-blue-200 bg-blue-50',
        'icon' => 'information-circle',
        'iconColor' => 'text-blue-600',
        'title' => 'text-blue-900',
        'body' => 'text-blue-700',
    ],
];
$config = $styles[$variant];
@endphp
<div
    {{ $attributes->merge([
        'class' => 'flex gap-4 rounded-xl border p-4 '.$config['wrapper']
    ]) }}>
    <x-dynamic-component
        :component="'heroicon-o-'.$config['icon']"
        class="h-6 w-6 shrink-0 {{$config['iconColor']}}"/>
    <div class="space-y-1">
        @isset($title)
            <h4 class="font-semibold {{$config['title']}}">
                {{ $title }}
            </h4>
        @endisset
        <div class="text-sm {{$config['body']}}">
            {{ $slot }}
        </div>
    </div>
</div>