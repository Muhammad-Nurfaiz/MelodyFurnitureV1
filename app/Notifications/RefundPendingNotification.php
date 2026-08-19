<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RefundPendingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Refund $refund,
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
            'type' => 'pending_refund',

            'title' => 'Refund menunggu diproses',

            'message' => sprintf(
                'Refund untuk pesanan #%s menunggu diproses.',
                $this->refund->order?->order_number
                    ?? $this->refund->order_id
            ),

            'refund_id' => $this->refund->id,

            'refund_number' => $this->refund->refund_number,

            'order_id' => $this->refund->order_id,

            'order_number' => $this->refund->order?->order_number,

            'amount' => (float) $this->refund->amount,

            'url' => route(
                'admin.orders.show',
                $this->refund->order
            ),
        ];
    }
}