<div
    {{ $attributes->merge([
        'class' => '
            grid
            gap-6
            sm:grid-cols-2
            xl:grid-cols-4'
    ]) }}
>
    {{ $slot }}
</div>