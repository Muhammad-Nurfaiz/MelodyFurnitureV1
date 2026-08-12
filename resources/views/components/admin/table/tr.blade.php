<tr
    {{ $attributes->merge([
        'class' => '
            transition-colors
            duration-150
            hover:bg-gray-100'
    ]) }}>
    {{ $slot }}
</tr>