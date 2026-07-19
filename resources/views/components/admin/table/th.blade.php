<th
    {{ $attributes->merge([
        'class' => '
            whitespace-nowrap
            px-6
            py-4
            text-left
            text-xs
            font-semibold
            uppercase
            tracking-wider
            text-gray-500'
    ]) }}>
    {{ $slot }}
</th>