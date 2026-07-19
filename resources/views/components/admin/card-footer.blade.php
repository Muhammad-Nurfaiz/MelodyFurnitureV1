<div
    {{ $attributes->merge([
        'class' => 'flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4'
    ]) }}>

    {{ $slot }}

</div>