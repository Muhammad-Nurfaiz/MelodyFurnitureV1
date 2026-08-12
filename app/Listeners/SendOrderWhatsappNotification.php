<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\SendWhatsappMessage;
use App\Services\Whatsapp\WhatsappNotificationService;

class SendOrderWhatsappNotification
{
    public function __construct(
        protected WhatsappNotificationService $notificationService,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $queue = $this->notificationService
            ->queueOrderStatusNotification(
                order: $event->order,
                status: $event->newStatus,
            );

        if ($queue === null) {
            return;
        }

        SendWhatsappMessage::dispatch(
            whatsappQueueId: $queue->id,
        );
    }
}