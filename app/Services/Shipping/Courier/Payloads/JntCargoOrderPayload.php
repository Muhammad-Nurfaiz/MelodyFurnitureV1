<?php

namespace App\Services\Shipping\Courier\Payloads;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSpecification;
use RuntimeException;

class JntCargoOrderPayload
{
    /**
     * Build J&T Cargo Create Order payload.
     */
    public function build(Order $order): array
    {
        $order->loadMissing([
            'items.product.specification',
        ]);

        if ($order->items->isEmpty()) {
            throw new RuntimeException(
                'Order tidak memiliki item untuk dikirim ke J&T Cargo.'
            );
        }

        $shippingAddress = $this->shippingAddress($order);

        $items = $order->items
            ->map(function (OrderItem $item) {
                return $this->mapItem($item);
            })
            ->values()
            ->all();

        $shipment = $this->shipmentData($order);

        return [
            /*
            |--------------------------------------------------------------------------
            | Customer / Order
            |--------------------------------------------------------------------------
            */
            'txlogisticId' => $order->order_number,
            'expressType' => 'FT',
            'orderType' => '2',
            'serviceType' => '01',
            'deliveryType' => '101',
            'payType' => 'PP_PM',

            /*
            |--------------------------------------------------------------------------
            | Sender
            |--------------------------------------------------------------------------
            */
            'sender' => $this->sender(),

            /*
            |--------------------------------------------------------------------------
            | Receiver
            |--------------------------------------------------------------------------
            */
            'receiver' => [
                'name' => $shippingAddress['recipient_name'],
                'mobile' => $this->normalizeMobile($shippingAddress['phone']),
                'countrycode' => 'IDN',
                'prov' => $shippingAddress['province'],
                'area' => $this->area($shippingAddress),
                'city' => $shippingAddress['city'],
                'address' => $shippingAddress['address'],
                'postcode' => $shippingAddress['postal_code'] ?? null,
            ],

            /*
            |--------------------------------------------------------------------------
            | Goods
            |--------------------------------------------------------------------------
            */
            'goodsType' => 'bm000006',

            /*
            |--------------------------------------------------------------------------
            | Shipment
            |--------------------------------------------------------------------------
            */
            'weight' => $shipment['weight'],
            'length' => $shipment['length'],
            'width' => $shipment['width'],
            'height' => $shipment['height'],

            /*
            |--------------------------------------------------------------------------
            | Package Quantity
            |--------------------------------------------------------------------------
            |
            | Saat ini 1 unit furniture dianggap 1 koli.
            |
            */
            'totalQuantity' => $shipment['total_quantity'],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */
            'items' => $items,

            /*
            |--------------------------------------------------------------------------
            | Optional
            |--------------------------------------------------------------------------
            */
            'remark' => 'Order Melody Furniture ' .
                $order->order_number,
        ];
    }

    /**
     * Static sender Melody Furniture.
     */
    protected function sender(): array
    {
        $sender = config(
            'shipping.couriers.jnt_cargo.sender',
            []
        );

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
            if (blank($sender[$field] ?? null)) {
                throw new RuntimeException(
                    "Data sender J&T Cargo [{$field}] belum dikonfigurasi."
                );
            }
        }

        return [
            'name' => $sender['name'],
            'mobile' => $this->normalizeMobile($sender['mobile']),
            'countrycode' => $sender['country_code'],
            'prov' => $sender['province'],
            'area' => $sender['area'],
            'city' => $sender['city'],
            'address' => $sender['address'],
            'postcode' => $sender['postcode'] ?? null,
        ];
    }

    /**
     * Map OrderItem ke format J&T items.
     */
    protected function mapItem(OrderItem $item): array
    {
        $product = $item->product;

        if (! $product) {
            throw new RuntimeException(
                "Product untuk order item [{$item->id}] tidak ditemukan."
            );
        }

        return [
            'itemType' => 'bm000006',
            'itemName' => $item->product_name,
            'number' => (string) $item->quantity,
            'itemValue' => (float) $item->subtotal,
            'priceCurrency' => 'IDR',
            'desc' => $item->product_sku
                ? 'SKU: ' . $item->product_sku
                : $item->product_name,
        ];
    }

    /**
     * Data berat dan dimensi shipment.
     */
    protected function shipmentData(Order $order): array
    {
        $totalQuantity = (int) $order->items->sum(
            fn (OrderItem $item) => (int) $item->quantity
        );

        if ($totalQuantity <= 0) {
            throw new RuntimeException(
                'Total quantity order tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Weight
        |--------------------------------------------------------------------------
        |
        | Prioritas:
        | 1. packing_weight
        | 2. weight
        |
        */
        $weight = 0;

        foreach ($order->items as $item) {
            $specification = $item->product?->specification;

            if (! $specification) {
                throw new RuntimeException(
                    "Product [{$item->product_name}] " .
                    'belum memiliki ProductSpecification.'
                );
            }

            $itemWeight = $this->packingWeight(
                $specification
            );

            $weight += $itemWeight * (int) $item->quantity;
        }

        if ($weight <= 0) {
            throw new RuntimeException(
                'Total berat shipment J&T Cargo tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Dimensions
        |--------------------------------------------------------------------------
        |
        | J&T Create Order hanya menyediakan satu set:
        | length, width, height.
        |
        | Untuk sementara mapper menggunakan dimensi item pertama.
        | Model multi-koli yang lebih detail dapat ditambahkan kemudian
        | tanpa mengubah Order/OrderItem.
        |
        */
        $firstItem = $order->items->first();

        $specification = $firstItem
            ?->product
            ?->specification;

        if (! $specification) {
            throw new RuntimeException(
                'ProductSpecification item pertama tidak ditemukan.'
            );
        }

        $dimensions = $this->parseDimensions(
            $specification->dimensions
        );

        return [
            'weight' => round($weight, 2),
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'total_quantity' => $totalQuantity,
        ];
    }

    /**
     * Gunakan packing_weight jika tersedia.
     */
    protected function packingWeight(
        ProductSpecification $specification
    ): float {
        if (
            $specification->packing_weight !== null &&
            (float) $specification->packing_weight > 0
        ) {
            return (float) $specification->packing_weight;
        }

        if (
            $specification->weight !== null &&
            (float) $specification->weight > 0
        ) {
            return (float) $specification->weight;
        }

        throw new RuntimeException('Product memiliki berat yang tidak valid.');
    }

    /**
     * Parse:
     *
     * 120 × 60 × 75 cm
     *
     * menjadi:
     *
     * length = 120
     * width  = 60
     * height = 75
     */
    protected function parseDimensions(
        ?string $dimensions
    ): array {
        if (blank($dimensions)) {
            throw new RuntimeException(
                'Product memiliki dimensions yang kosong.'
            );
        }

        $normalized = str_replace(
            ['×', 'x', 'X'],
            'x',
            trim($dimensions)
        );

        $normalized = preg_replace(
            '/\s+/',
            '',
            $normalized
        );

        if (
            ! preg_match(
                '/^([\d.]+)x([\d.]+)x([\d.]+)(?:cm)?$/i',
                $normalized,
                $matches
            )
        ) {
            throw new RuntimeException(
                "Format dimensions [{$dimensions}] tidak valid. " .
                'Format yang diharapkan: 120 × 60 × 75 cm.'
            );
        }

        return [
            'length' => (float) $matches[1],
            'width' => (float) $matches[2],
            'height' => (float) $matches[3],
        ];
    }

    /**
     * Ambil alamat penerima dari snapshot Order.
     */
    protected function shippingAddress(Order $order): array {
        $address = $order->shipping_address;

        if (! is_array($address)) {
            throw new RuntimeException('Shipping address order tidak valid.');
        }

        $required = [
            'recipient_name',
            'phone',
            'province',
            'city',
            'address',
        ];

        foreach ($required as $field) {
            if (blank($address[$field] ?? null)) {
                throw new RuntimeException("Shipping address [{$field}] tidak lengkap.");
                }
        }

        return $address;
    }

    /**
     * Area penerima.
     *
     * Area harus berasal dari mapping alamat
     * yang sesuai dengan data J&T Cargo.
     */
    protected function area(array $address): string
    {
        if (blank($address['area'] ?? null)) {
            throw new RuntimeException('Area penerima belum tersedia pada shipping address order.');
        }

        return $address['area'];
    }

    /**
     * Normalisasi nomor HP.
     */
    protected function normalizeMobile(
        string $mobile
    ): string {
        $mobile = preg_replace(
            '/[^0-9]/',
            '',
            $mobile
        );

        if (str_starts_with($mobile, '0')) {
            return '62' . substr($mobile, 1);
        }

        if (str_starts_with($mobile, '62')) {
            return $mobile;
        }

        return $mobile;
    }
}