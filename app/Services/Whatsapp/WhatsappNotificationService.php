<?php

namespace App\Services\Whatsapp;

use App\Models\Order;
use App\Models\WhatsappQueue;
use App\Jobs\SendWhatsappMessage;
use RuntimeException;

class WhatsappNotificationService
{
    public function __construct(
        protected WhatsappMessageTemplateService $templateService,
    ) {}

    /**
     * Membuat pesan WhatsApp berdasarkan perubahan status order
     * dan memasukkannya ke queue.
     */
    public function queueOrderStatusNotification(
        Order $order,
        string $status,
    ): ?WhatsappQueue {
        /*
        |--------------------------------------------------------------------------
        | Generate Message
        |--------------------------------------------------------------------------
        */

        $message = $this->templateService->orderStatusChanged(
            order: $order,
            status: $status,
        );

        /*
        |--------------------------------------------------------------------------
        | Tidak ada template
        |--------------------------------------------------------------------------
        */

        if ($message === null) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Nomor Customer
        |--------------------------------------------------------------------------
        */

        if (blank($order->customer_phone)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Create WhatsApp Queue
        |--------------------------------------------------------------------------
        */

        return WhatsappQueue::create([
            'order_id' => $order->id,
            'phone_target' => $order->customer_phone,
            'message_text' => $message,
            'status' => 'pending',
            'error_log' => null,
        ]);
    }

    public function retry(WhatsappQueue $queue): void
    {
        if ($queue->status !== 'failed') {
            throw new RuntimeException(
                'Pesan WhatsApp hanya dapat di-retry jika statusnya failed.'
            );
        }

        $queue->update([
            'status' => 'pending',
            'error_log' => null,
            'sent_at' => null,
        ]);

        SendWhatsappMessage::dispatch($queue->id);
    }
}