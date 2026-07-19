@props([
    'rows' => 5,
])
<div
    {{ $attributes->merge([
        'class' => 'animate-pulse space-y-3'
    ]) }}>
    @for($i = 0; $i < $rows; $i++)
        <div
            class="h-12 rounded-lg bg-gray-200">
        </div>
    @endfor
</div>