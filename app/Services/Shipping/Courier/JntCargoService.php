<?php

namespace App\Services\Shipping\Courier;

use App\Models\Order;
use App\Services\Shipping\Courier\Clients\JntCargoClient;
use App\Services\Shipping\Courier\JntCargoTrackingStatusMapper;

class JntCargoService implements CourierInterface
{
    public function __construct(
        protected JntCargoClient $client,
        protected JntCargoTrackingStatusMapper $trackingStatusMapper,
    ) {}

    /**
     * Membuat shipment.
     */
    public function createShipment(
        Order $order
    ): CourierShipmentResult {

        return $this->client->createShipment(
            $order
        );
    }

    /**
     * Update shipment.
     */
    public function updateShipment(
        Order $order
    ): CourierShipmentResult {

        return CourierShipmentResult::failed(
            'Integrasi update shipment J&T Cargo belum dikonfigurasi.'
        );
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(
        Order $order
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | API integration
        |--------------------------------------------------------------------------
        |
        | Endpoint cancel belum diketahui sampai dokumentasi resmi
        | J&T Cargo tersedia.
        |
        */

        return false;
    }

    /**
     * Tracking shipment.
     */
    public function tracking(
        Order $order
    ): CourierShipmentResult {
        try {
            $trackingNumber = $order->tracking_number
                ?? $order->shipment?->tracking_number;

            if (blank($trackingNumber)) {
                return CourierShipmentResult::failed(
                    'Tracking number J&T Cargo belum tersedia.'
                );
            }

            $response = $this->client->traceRaw(
                $trackingNumber
            );

            if (! $response->successful()) {
                return CourierShipmentResult::failed(
                    'Gagal mengambil tracking J&T Cargo. ' .
                    'HTTP status: ' . $response->status()
                );
            }

            $code = (string) $response->json('code');

            if ($code !== '1') {
                return CourierShipmentResult::failed(
                    (string) (
                        $response->json('msg')
                        ?? 'J&T Cargo gagal mengambil informasi tracking.'
                    )
                );
            }

            $data = $response->json('data');

            if (! is_array($data) || empty($data)) {
                return CourierShipmentResult::failed(
                    'J&T Cargo tidak mengembalikan data tracking.'
                );
            }

            $trackingData = collect($data)
                ->firstWhere(
                    'billCode',
                    $trackingNumber
                );

            if (! is_array($trackingData)) {
                return CourierShipmentResult::failed(
                    'Data tracking untuk AWB [' .
                    $trackingNumber .
                    '] tidak ditemukan.'
                );
            }

            $details = $trackingData['details'] ?? [];

            if (! is_array($details)) {
                $details = [];
            }

            $latestDetail = $details[0] ?? null;

            $mappedStatus = $this->mappedTrackingStatus(
                $details
            );

            return CourierShipmentResult::success(
                bookingCode: $order->shipment?->booking_code,
                trackingNumber: $trackingNumber,
                labelUrl: $order->shipment?->label_url,
                status: $order->shipment?->status
                    ?? 'waiting_pickup',
                metadata: [
                    'billCode' => $trackingData['billCode'] ?? $trackingNumber,
                    'details' => $details,
                    'latest_detail' => $latestDetail,
                    'mapped_status' => $mappedStatus,
                    'synced_at' => now()->toISOString(),
                ],
                message: (string) (
                    $response->json('msg')
                    ?? 'Tracking J&T Cargo berhasil diambil.'
                ),
            );
        } catch (\Throwable $e) {
            return CourierShipmentResult::failed(
                $e->getMessage()
            );
        }
    }

    protected function mappedTrackingStatus(
        array $details
    ): ?string {
        if (empty($details)) {
            return null;
        }

        $latestDetail = $details[0] ?? null;

        if (! is_array($latestDetail)) {
            return null;
        }

        return $this->trackingStatusMapper->map(
            isset($latestDetail['scanCode'])
                ? (string) $latestDetail['scanCode']
                : null
        );
    }
}