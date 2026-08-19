<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingOrderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
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
            'type' => 'pending_order',

            'title' => 'Pesanan pending terlalu lama',

            'message' => sprintf(
                'Pesanan #%s masih berstatus pending.',
                $this->order->order_number
            ),

            'order_id' => $this->order->id,

            'order_number' => $this->order->order_number,

            'url' => route(
                'admin.orders.show',
                $this->order
            ),
        ];
    }
}