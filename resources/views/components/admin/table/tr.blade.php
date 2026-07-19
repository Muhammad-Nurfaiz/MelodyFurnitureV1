<tr
    {{ $attributes->merge([
        'class' => '
            transition-colors
            duration-150
            hover:bg-gray-50'
    ]) }}>
    {{ $slot }}
</tr>