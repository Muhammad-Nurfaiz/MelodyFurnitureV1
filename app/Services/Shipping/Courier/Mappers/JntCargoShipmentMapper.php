<?php

namespace App\Services\Shipping\Courier\Mappers;

use App\Models\Order;
use App\Models\ProductSpecification;
use RuntimeException;

class JntCargoShipmentMapper
{
    /**
     * Static sender configuration.
     *
     * Jangan hardcode credential J&T di sini.
     * Data sender adalah data pengirim/toko, bukan credential API.
     */
    protected array $sender;

    public function __construct()
    {
        $this->sender = config(
            'shipping.couriers.jnt_cargo.sender',
            []
        );
    }

    /**
     * Convert Laravel Order into J&T Cargo Create Order payload.
     */
    public function map(Order $order): array
    {
        $items = $order->items;

        if ($items->isEmpty()) {
            throw new RuntimeException(
                'Order tidak memiliki item untuk dikirim ke J&T Cargo.'
            );
        }

        $firstItem = $items->first();

        if (! $firstItem->product) {
            throw new RuntimeException(
                'Produk pada order tidak ditemukan.'
            );
        }

        $specification = $firstItem->product->specification;

        if (! $specification) {
            throw new RuntimeException(
                "ProductSpecification untuk produk [{$firstItem->product_name}] belum tersedia."
            );
        }

        $shippingAddress = $order->shipping_address ?? [];

        $this->validateSender();
        $this->validateReceiver($shippingAddress);

        $dimensions = $this->parseDimensions(
            $specification->dimensions
        );

        $weight = $this->resolveWeight($specification);

        return [
            /*
             * J&T Order Identification
             */
            'txlogisticId' => (string) $order->order_number,

            /*
             * Confirmed by J&T PIC
             */
            'expressType' => 'FT',
            'orderType' => '2',
            'serviceType' => '01',
            'deliveryType' => '101',
            'payType' => 'PP_PM',

            /*
             * Furniture
             */
            'goodsType' => 'bm000006',

            /*
             * Shipment information
             */
            'weight' => $weight,

            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],

            /*
             * Temporary assumption:
             * 1 product unit = 1 koli/package.
             */
            'totalQuantity' => $this->resolveTotalQuantity($items),

            /*
             * Sender / Receiver
             */
            'sender' => $this->mapSender(),

            'receiver' => $this->mapReceiver(
                $shippingAddress
            ),

            /*
             * J&T item detail
             */
            'items' => $this->mapItems($items),
        ];
    }

    protected function validateSender(): void
    {
        $required = [
            'name',
            'mobile',
            'country_code',
            'province',
            'area',
            'city',
            'address',
        ];

        foreach ($required as $field) {
            if (
                ! isset($this->sender[$field]) ||
                blank($this->sender[$field])
            ) {
                throw new RuntimeException(
                    "Data sender J&T Cargo [{$field}] belum dikonfigurasi."
                );
            }
        }
    }

    protected function validateReceiver(array $address): void
    {
        $required = [
            'recipient_name',
            'phone',
            'province',
            'city',
            'address',
        ];

        foreach ($required as $field) {
            if (
                ! isset($address[$field]) ||
                blank($address[$field])
            ) {
                throw new RuntimeException(
                    "Data alamat pengiriman [{$field}] belum tersedia."
                );
            }
        }

        /*
         * Area wajib mengikuti data J&T.
         *
         * Jangan melakukan guessing berdasarkan city/regency.
         */
        if (
            ! isset($address['area']) ||
            blank($address['area'])
        ) {
            throw new RuntimeException(
                'Area J&T Cargo pada alamat pengiriman belum tersedia. '
                . 'Gunakan data dari endpoint /order/getAddress.'
            );
        }
    }

    protected function mapSender(): array
    {
        return [
            'name' => (string) $this->sender['name'],
            'Mobile' => (string) $this->sender['mobile'],
            'Countrycode' => (string) $this->sender['country_code'],
            'Prov' => (string) $this->sender['province'],
            'Area' => (string) $this->sender['area'],
            'City' => (string) $this->sender['city'],
            'Address' => (string) $this->sender['address'],
        ];
    }

    protected function mapReceiver(array $address): array
    {
        return [
            'name' => (string) $address['recipient_name'],
            'Mobile' => (string) $address['phone'],
            'Countrycode' => 'ID',
            'Prov' => (string) $address['province'],
            'Area' => (string) $address['area'],
            'City' => (string) $address['city'],
            'Address' => (string) $address['address'],
        ];
    }

    protected function resolveWeight(
        ProductSpecification $specification
    ): float {
        /*
         * Packing weight is preferred because this is the
         * shipping weight.
         */
        $weight = $specification->packing_weight
            ?? $specification->weight;

        if ($weight === null || (float) $weight <= 0) {
            throw new RuntimeException(
                'Berat produk untuk pengiriman J&T Cargo belum tersedia.'
            );
        }

        return (float) $weight;
    }

    protected function parseDimensions(
        ?string $dimensions
    ): array {
        if (blank($dimensions)) {
            throw new RuntimeException(
                'Dimensi produk untuk pengiriman J&T Cargo belum tersedia.'
            );
        }

        /*
         * Supported examples:
         *
         * 120 × 60 × 75 cm
         * 120 x 60 x 75 cm
         * 120 X 60 X 75 CM
         */
        $normalized = str_replace(
            ['×', 'X'],
            'x',
            trim($dimensions)
        );

        $normalized = preg_replace(
            '/\s+/',
            ' ',
            $normalized
        );

        if (! is_string($normalized)) {
            throw new RuntimeException(
                "Format dimensi produk tidak valid: {$dimensions}"
            );
        }

        if (
            ! preg_match(
                '/^\s*([\d.,]+)\s*x\s*([\d.,]+)\s*x\s*([\d.,]+)\s*(?:cm)?\s*$/i',
                $normalized,
                $matches
            )
        ) {
            throw new RuntimeException(
                "Format dimensi produk tidak dapat diproses: {$dimensions}"
            );
        }

        return [
            'length' => $this->normalizeNumber($matches[1]),
            'width' => $this->normalizeNumber($matches[2]),
            'height' => $this->normalizeNumber($matches[3]),
        ];
    }

    protected function normalizeNumber(string $value): float
    {
        $value = trim($value);

        /*
         * Support:
         * 120
         * 120.5
         * 120,5
         */
        if (
            str_contains($value, ',') &&
            ! str_contains($value, '.')
        ) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    protected function resolveTotalQuantity($items): int
    {
        return (int) $items->sum(
            fn ($item) => (int) $item->quantity
        );
    }

    protected function mapItems($items): array
    {
        return $items->map(function ($item) {
            return [
                'itemType' => 'bm000006',
                'itemName' => (string) $item->product_name,
                'number' => (int) $item->quantity,
                'itemValue' => (float) $item->unit_price,
                'priceCurrency' => 'IDR',
                'desc' => (string) $item->product_name,
            ];
        })->values()->all();
    }
}