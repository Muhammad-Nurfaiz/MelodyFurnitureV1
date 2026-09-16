<?php

namespace App\Services\Shipping\Webhooks;

use App\Models\Shipment;
use App\Services\Shipping\Courier\CourierShipmentResult;
use App\Services\Shipping\Courier\JntCargoTrackingStatusMapper;
use App\Services\Shipping\ShipmentService;
use App\Models\JntCargoWebhookEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JntCargoWebhookService
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected JntCargoTrackingStatusMapper $trackingStatusMapper,
    ) {}

    /**
     * Memproses webhook tracking J&T Cargo.
     */
    public function handle(array $payload): array
    {
        $entries = $this->extractEntries($payload);

        if (empty($entries)) {
            throw new RuntimeException(
                'Payload webhook J&T Cargo tidak memiliki data shipment.'
            );
        }

        $processed = [];
        $ignored = [];

        foreach ($entries as $entry) {
            $result = $this->processEntry($entry);

            if ($result['processed']) {
                $processed[] = $result;
            } else {
                $ignored[] = $result;
            }
        }

        return [
            'processed' => $processed,
            'ignored' => $ignored,
            'processed_count' => count($processed),
            'ignored_count' => count($ignored),
        ];
    }

    /**
     * Memproses webhook Order Status Return J&T Cargo.
     */
    public function handleOrderStatus(array $payload): array
    {
        $entry = $this->extractOrderStatusEntry($payload);

        if (empty($entry)) {
            throw new RuntimeException(
                'Payload Order Status Return J&T Cargo tidak valid.'
            );
        }

        return $this->processOrderStatusEntry($entry);
    }

    /**
     * Normalisasi struktur payload J&T.
     *
     * Kita mendukung:
     *
     * data: [
     *     [
     *         billCode => "...",
     *         details => [...]
     *     ]
     * ]
     *
     * maupun satu object shipment.
     */
    protected function extractEntries(array $payload): array
    {
        /*
        * J&T Cargo mengirim webhook sebagai
        * application/x-www-form-urlencoded dengan field:
        *
        * bizContent = JSON string
        */
        if (isset($payload['bizContent'])) {
            $bizContent = $payload['bizContent'];

            if (is_string($bizContent)) {
                $decoded = json_decode($bizContent, true);

                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                    return [];
                }

                $payload = $decoded;
            } elseif (is_array($bizContent)) {
                $payload = $bizContent;
            }
        }

        $data = $payload['data'] ?? $payload;

        if (isset($data['billCode']) || isset($data['billcode'])) {
            return [$data];
        }

        if (! is_array($data)) {
            return [];
        }

        return array_is_list($data) ? $data : [$data];
    }

    /**
     * Normalisasi payload Order Status Return J&T Cargo.
     *
     * Format request:
     *
     * bizContent = JSON string
     *
     * {
     *     "txlogisticId": "...",
     *     "billCode": "...",
     *     "jtOrderId": "...",
     *     "scanType": "...",
     *     "time": "..."
     * }
     */
    protected function extractOrderStatusEntry(array $payload): array
    {
        if (! isset($payload['bizContent'])) {
            return [];
        }

        $bizContent = $payload['bizContent'];

        if (is_string($bizContent)) {
            $decoded = json_decode($bizContent, true);

            if (
                json_last_error() !== JSON_ERROR_NONE
                || ! is_array($decoded)
            ) {
                return [];
            }

            return $decoded;
        }

        return is_array($bizContent)
            ? $bizContent
            : [];
    }

    /**
     * Proses satu Order Status Return J&T Cargo.
     */
    protected function processOrderStatusEntry(array $entry): array
    {
        $trackingNumber = $entry['billCode']
            ?? $entry['billcode']
            ?? null;

        $orderNumber = $entry['txlogisticId']
            ?? $entry['txLogisticId']
            ?? null;

        $shipment = null;

        if (filled($trackingNumber)) {
            $shipment = Shipment::query()
                ->where(
                    'tracking_number',
                    trim((string) $trackingNumber)
                )
                ->first();
        }

        if (! $shipment && filled($orderNumber)) {
            $shipment = Shipment::query()
                ->whereHas('order', function ($query) use ($orderNumber) {
                    $query->where(
                        'order_number',
                        trim((string) $orderNumber)
                    );
                })
                ->first();
        }

        if (! $shipment) {
            return [
                'processed' => false,
                'tracking_number' => filled($trackingNumber)
                    ? trim((string) $trackingNumber)
                    : null,
                'order_number' => filled($orderNumber)
                    ? trim((string) $orderNumber)
                    : null,
                'reason' => 'Shipment lokal tidak ditemukan.',
            ];
        }

        $scanType = $entry['scanType']
            ?? $entry['scantype']
            ?? null;

        $mappedStatus = $this->trackingStatusMapper->mapOrderStatus(
            $scanType !== null
                ? (string) $scanType
                : null
        );

        $resolvedTrackingNumber = filled($trackingNumber)
            ? trim((string) $trackingNumber)
            : $shipment->tracking_number;

        $eventType = 'jnt_cargo_order_status';

        $eventKey = $this->generateEventKey(
            eventType: $eventType,
            payload: $entry
        );

        $result = CourierShipmentResult::success(
            bookingCode: $shipment->booking_code,
            trackingNumber: $resolvedTrackingNumber,
            labelUrl: $shipment->label_url,
            status: $mappedStatus,
            metadata: [
                'source' => 'jnt_cargo_order_status_webhook',
                'txlogisticId' => $orderNumber,
                'billCode' => $resolvedTrackingNumber,
                'jtOrderId' => $entry['jtOrderId'] ?? null,
                'scanType' => $scanType,
                'mapped_status' => $mappedStatus,
                'time' => $entry['time'] ?? null,
                'networkName' => $entry['networkName'] ?? null,
                'pickStaffName' => $entry['pickStaffName'] ?? null,
                'pickStaffPhone' => $entry['pickStaffPhone'] ?? null,
                'weight' => $entry['Weight']
                    ?? $entry['weight']
                    ?? null,
                'reason' => $entry['reason'] ?? null,
                'sumFreight' => $entry['sumFreight'] ?? null,
                'customerCost' => $entry['customerCost'] ?? null,
                'insuranceCost' => $entry['insuranceCost'] ?? null,
                'webhook_received_at' => now()->toISOString(),
                'raw_payload' => $entry,
            ],
            message: 'Order Status Return J&T Cargo berhasil diproses.',
        );

        $inserted = 0;

        DB::transaction(function () use (
            $shipment,
            $result,
            $mappedStatus,
            $eventType,
            $eventKey,
            $resolvedTrackingNumber,
            $orderNumber,
            &$inserted
        ) {
            $inserted = JntCargoWebhookEvent::query()->insertOrIgnore([
                'event_type' => $eventType,
                'event_key' => $eventKey,
                'tracking_number' => $resolvedTrackingNumber,
                'order_number' => filled($orderNumber)
                    ? trim((string) $orderNumber)
                    : null,
                'payload' => json_encode(
                    $result->metadata['raw_payload'] ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'processed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
            * Jika insert gagal karena event_key sudah ada,
            * request ini adalah duplicate dan tidak boleh
            * menjalankan sinkronisasi bisnis lagi.
            */
            if ($inserted !== 1) {
                return;
            }

            $this->shipmentService->syncTracking(
                $shipment,
                $result
            );

            $this->shipmentService->syncCourierStatus(
                $shipment->fresh(),
                $mappedStatus
            );

            JntCargoWebhookEvent::query()
                ->where('event_type', $eventType)
                ->where('event_key', $eventKey)
                ->update([
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        if ($inserted !== 1) {
            return [
                'processed' => false,
                'duplicate' => true,
                'shipment_id' => $shipment->id,
                'tracking_number' => $resolvedTrackingNumber,
                'order_number' => $orderNumber,
                'scan_type' => $scanType,
                'mapped_status' => $mappedStatus,
                'reason' => 'Webhook Order Status sudah pernah diproses.',
            ];
        }

        return [
            'processed' => true,
            'duplicate' => false,
            'shipment_id' => $shipment->id,
            'tracking_number' => $resolvedTrackingNumber,
            'order_number' => $orderNumber,
            'scan_type' => $scanType,
            'mapped_status' => $mappedStatus,
        ];
    }

    /**
     * Proses satu AWB.
     */
    protected function processEntry(array $entry): array
    {
        $trackingNumber = $entry['billCode']
            ?? $entry['billcode']
            ?? null;

        if (blank($trackingNumber)) {
            return [
                'processed' => false,
                'reason' => 'billCode tidak ditemukan.',
            ];
        }

        $trackingNumber = trim((string) $trackingNumber);

        /*
        |--------------------------------------------------------------------------
        | Find Shipment
        |--------------------------------------------------------------------------
        */

        $shipment = Shipment::query()
            ->where('tracking_number', $trackingNumber)
            ->first();

        if (! $shipment) {
            return [
                'processed' => false,
                'tracking_number' => $trackingNumber,
                'reason' => 'Shipment lokal tidak ditemukan.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Tracking Details
        |--------------------------------------------------------------------------
        */

        $details = $entry['details']
            ?? $entry['detail']
            ?? [];

        if (! is_array($details)) {
            $details = [];
        }

        $latestDetail = $details[0] ?? null;

        if (! is_array($latestDetail)) {
            $latestDetail = null;
        }

        $scanCode = $latestDetail['scanCode']
            ?? $latestDetail['scancode']
            ?? null;

        $mappedStatus = $this->trackingStatusMapper->map(
            $scanCode !== null
                ? (string) $scanCode
                : null
        );

        /*
        |--------------------------------------------------------------------------
        | Idempotency
        |--------------------------------------------------------------------------
        */

        $eventType = 'jnt_cargo_logistics_trackback';

        $eventKey = $this->generateEventKey(
            eventType: $eventType,
            payload: $entry,
        );

        $eventExists = JntCargoWebhookEvent::query()
            ->where('event_type', $eventType)
            ->where('event_key', $eventKey)
            ->exists();

        if ($eventExists) {
            return [
                'processed' => false,
                'duplicate' => true,
                'shipment_id' => $shipment->id,
                'tracking_number' => $trackingNumber,
                'scan_code' => $scanCode,
                'mapped_status' => $mappedStatus,
                'event_key' => $eventKey,
                'reason' => 'Webhook event sudah pernah diterima.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Courier Result
        |--------------------------------------------------------------------------
        */

        $result = CourierShipmentResult::success(
            bookingCode: $shipment->booking_code,
            trackingNumber: $trackingNumber,
            labelUrl: $shipment->label_url,
            status: $mappedStatus,
            metadata: [
                'source' => 'jnt_cargo_webhook',
                'billCode' => $trackingNumber,
                'details' => $details,
                'latest_detail' => $latestDetail,
                'mapped_status' => $mappedStatus,
                'webhook_received_at' => now()->toISOString(),
                'raw_payload' => $entry,
            ],
            message: 'Webhook J&T Cargo berhasil diproses.',
        );

        /*
        |--------------------------------------------------------------------------
        | Process Event
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $eventType,
            $eventKey,
            $trackingNumber,
            $shipment,
            $result,
            $mappedStatus,
            $entry
        ) {
            /*
            |--------------------------------------------------------------------------
            | Register Event
            |--------------------------------------------------------------------------
            */

            $inserted = JntCargoWebhookEvent::query()
                ->insertOrIgnore([
                    'event_type' => $eventType,
                    'event_key' => $eventKey,
                    'tracking_number' => $trackingNumber,
                    'order_number' => $shipment->order?->order_number,
                    'payload' => json_encode(
                        $entry,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'processed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Concurrent Duplicate Protection
            |--------------------------------------------------------------------------
            */

            if ($inserted !== 1) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Sync Tracking
            |--------------------------------------------------------------------------
            */

            $this->shipmentService->syncTracking(
                $shipment,
                $result
            );

            /*
            |--------------------------------------------------------------------------
            | Sync Courier Status
            |--------------------------------------------------------------------------
            */

            $this->shipmentService->syncCourierStatus(
                $shipment->fresh(),
                $mappedStatus
            );

            /*
            |--------------------------------------------------------------------------
            | Mark Event Processed
            |--------------------------------------------------------------------------
            */

            JntCargoWebhookEvent::query()
                ->where('event_type', $eventType)
                ->where('event_key', $eventKey)
                ->update([
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return [
            'processed' => true,
            'duplicate' => false,
            'shipment_id' => $shipment->id,
            'tracking_number' => $trackingNumber,
            'scan_code' => $scanCode,
            'mapped_status' => $mappedStatus,
            'event_key' => $eventKey,
        ];
    }

    protected function generateEventKey(
        string $eventType,
        array $payload
    ): string {
        $canonicalPayload = $this->canonicalizePayload($payload);

        return hash(
            'sha256',
            $eventType . '|' . json_encode(
                $canonicalPayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }

    protected function canonicalizePayload(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn ($item) => $this->canonicalizePayload($item),
                $value
            );
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizePayload($item);
        }

        return $value;
    }

    protected function registerEvent(
        string $eventType,
        array $payload,
        ?string $trackingNumber = null,
        ?string $orderNumber = null
    ): array {
        $eventKey = $this->generateEventKey(
            $eventType,
            $payload
        );

        $inserted = JntCargoWebhookEvent::query()
            ->insertOrIgnore([
                'event_type' => $eventType,
                'event_key' => $eventKey,
                'tracking_number' => $trackingNumber,
                'order_number' => $orderNumber,
                'payload' => json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'processed_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'inserted' => $inserted === 1,
            'event_key' => $eventKey,
        ];
    }

    protected function markEventProcessed(
        string $eventType,
        string $eventKey
    ): void {
        JntCargoWebhookEvent::query()
            ->where('event_type', $eventType)
            ->where('event_key', $eventKey)
            ->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}