@props([
    'original' => 0,
    'discount' => null,
    'sale' => false,
    'currency' => 'Rp',
])

@php

$original = (float) $original;

$discount = $discount !== null
    ? (float) $discount
    : null;

$finalPrice = $sale && $discount
    ? $discount
    : $original;

$percentage = 0;

if ($sale && $discount && $original > 0) {

    $percentage = round(
        (($original - $discount) / $original) * 100
    );

}

$format = fn ($price) => $currency.' '.number_format(
    $price,
    0,
    ',',
    '.'
);

@endphp

<div class="flex flex-col">

    <div
        class="font-semibold text-gray-900">

        {{ $format($finalPrice) }}

    </div>

    @if($sale && $discount)

        <div
            class="mt-1 flex items-center gap-2">

            <span
                class="text-xs text-gray-400 line-through">

                {{ $format($original) }}

            </span>

            <x-admin.badge
                variant="danger"
                size="sm">

                -{{ $percentage }}%

            </x-admin.badge>

        </div>

    @endif

</div>