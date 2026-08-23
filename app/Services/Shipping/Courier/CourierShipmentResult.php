<?php

namespace App\Services\Shipping\Courier;

class CourierShipmentResult
{
    public function __construct(
        public bool $success,
        public ?string $bookingCode = null,
        public ?string $trackingNumber = null,
        public ?string $labelUrl = null,
        public ?string $status = null,
        public array $metadata = [],
        public ?string $message = null,
    ) {}

    public static function success(
        ?string $bookingCode = null,
        ?string $trackingNumber = null,
        ?string $labelUrl = null,
        ?string $status = null,
        array $metadata = [],
        ?string $message = null,
    ): self {
        return new self(
            success: true,
            bookingCode: $bookingCode,
            trackingNumber: $trackingNumber,
            labelUrl: $labelUrl,
            status: $status,
            metadata: $metadata,
            message: $message,
        );
    }

    public static function failed(
        string $message,
        array $metadata = [],
    ): self {
        return new self(
            success: false,
            metadata: $metadata,
            message: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'booking_code' => $this->bookingCode,
            'tracking_number' => $this->trackingNumber,
            'label_url' => $this->labelUrl,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'message' => $this->message,
        ];
    }
}