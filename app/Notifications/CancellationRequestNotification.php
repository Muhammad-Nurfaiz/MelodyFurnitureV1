<?php

namespace App\Notifications;

use App\Models\OrderCancelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CancellationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderCancelRequest $cancellationRequest,
    ) {}

    /**
     * Notification channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Database notification payload.
     */
    public function toDatabase(object $notifiable): array
    {
        $order = $this->cancellationRequest->order;

        return [
            'type' => 'cancellation_request',

            'title' => 'Permintaan pembatalan pesanan',

            'message' => sprintf(
                'Customer mengajukan pembatalan pesanan #%s.',
                $order?->order_number
                    ?? $this->cancellationRequest->order_id
            ),

            'cancellation_request_id' =>
                $this->cancellationRequest->id,

            'order_id' =>
                $this->cancellationRequest->order_id,

            'order_number' =>
                $order?->order_number,

            'customer_id' =>
                $this->cancellationRequest->customer_id,

            'reason' =>
                $this->cancellationRequest->reason,

            'previous_status' =>
                $this->cancellationRequest->previous_status,

            'url' => route(
                'admin.orders.show',
                $order
            ),
        ];
    }
}