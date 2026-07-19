<div class="overflow-hidden border border-gray-200">
    <div class="overflow-x-auto">
        <table
            {{ $attributes->merge([
                'class' => 'min-w-full border-separate border-spacing-0'
            ]) }}>
            {{ $slot }}
        </table>
    </div>
</div>