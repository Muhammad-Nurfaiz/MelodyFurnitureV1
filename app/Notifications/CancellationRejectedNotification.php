<?php

namespace App\Notifications;

use App\Models\OrderCancelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CancellationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OrderCancelRequest $request,
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
        return [
            'type' => 'cancellation_rejected',

            'title' => 'Pembatalan pesanan ditolak',

            'message' => sprintf(
                'Permintaan pembatalan pesanan #%s telah ditolak.',
                $this->request->order?->order_number
                    ?? $this->request->order_id
            ),

            'cancellation_request_id' => $this->request->id,

            'order_id' => $this->request->order_id,

            'order_number' => $this->request->order?->order_number,

            'customer_id' => $this->request->customer_id,

            'reason' => $this->request->reason,

            'admin_id' => $this->request->approved_by,

            'admin_notes' => $this->request->admin_notes,

            'url' => route(
                'admin.orders.show',
                $this->request->order
            ),
        ];
    }
}