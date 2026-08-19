<?php

namespace App\Notifications;

use App\Models\OrderCancelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CancellationApprovedNotification extends Notification
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
            'type' => 'cancellation_approved',

            'title' => 'Pembatalan pesanan disetujui',

            'message' => sprintf(
                'Permintaan pembatalan pesanan #%s telah disetujui.',
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

            'admin_id' =>
                $this->cancellationRequest->approved_by,

            'admin_notes' =>
                $this->cancellationRequest->admin_notes,

            'url' => route(
                'admin.orders.show',
                $order
            ),
        ];
    }
}