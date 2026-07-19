<div
    {{ $attributes->merge([
        'class' => '
            flex
            flex-col
            gap-4
            border-b
            border-gray-200
            p-5
            lg:flex-row
            lg:items-center
            lg:justify-between'
    ]) }}
>
    {{-- Left --}}
    <div class="flex flex-1 items-center">
        {{ $left ?? '' }}
    </div>
    {{-- Right --}}
    <div class="flex flex-wrap items-center justify-end gap-3">
        {{ $right ?? '' }}
    </div>
</div>