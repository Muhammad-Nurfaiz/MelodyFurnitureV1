<?php

namespace App\Services\Shipping\Courier;

class JntCargoTrackingStatusMapper
{
    /**
     * Map J&T Cargo scan code ke status shipment internal Melody.
     */
    public function map(?string $scanCode): ?string
    {
        return match ((string) $scanCode) {
            '1' => 'picked_up',

            '3',
            '4',
            '5' => 'in_transit',

            '10' => 'delivered',

            default => null,
        };
    }

    /**
     * Map J&T Cargo Order Status Return scanType
     * ke status shipment internal Melody.
     *
     * Catatan:
     * - Status cancelled dan pickupFail belum dipetakan
     *   ke workflow internal Melody.
     */
    public function mapOrderStatus(?string $scanType): ?string
    {
        $normalized = strtolower(trim((string) $scanType));

        // pickupFail tidak boleh dianggap sebagai pickup berhasil.
        if (
            str_contains($normalized, 'pickupfail')
            || str_contains($normalized, 'pickup fail')
        ) {
            return null;
        }

        return match (true) {
            str_contains($normalized, 'deploy')
                || str_contains($normalized, 'assigned')
                || str_contains($normalized, 'sprinter')
                => 'waiting_pickup',

            str_contains($normalized, 'pickup')
                || str_contains($normalized, 'collect')
                || str_contains($normalized, 'ambil')
                => 'picked_up',

            default => null,
        };
    }
}