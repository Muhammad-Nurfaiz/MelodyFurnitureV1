<?php

namespace App\Jobs;

use App\Models\WhatsappQueue;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendWhatsappMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $whatsappQueueId,
    ) {}

    public function handle(
        WhatsappSenderService $senderService,
    ): void {
        $queue = WhatsappQueue::find($this->whatsappQueueId);

        /*
        |--------------------------------------------------------------------------
        | Queue Tidak Ditemukan
        |--------------------------------------------------------------------------
        */

        if (! $queue) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Idempotent
        |--------------------------------------------------------------------------
        |
        | Job hanya boleh memproses queue dengan status pending.
        |
        */

        if ($queue->status !== 'pending') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Processing
        |--------------------------------------------------------------------------
        */

        $queue->update([
            'status' => 'processing',
            'attempts' => $queue->attempts + 1,
            'error_log' => null,
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | Send WhatsApp
            |--------------------------------------------------------------------------
            */

            $senderService->send(
                phone: $queue->phone_target,
                message: $queue->message_text,
            );

            /*
            |--------------------------------------------------------------------------
            | Mark Success
            |--------------------------------------------------------------------------
            */

            $queue->update([
                'status' => 'success',
                'sent_at' => now(),
                'error_log' => null,
            ]);

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Mark Failed
            |--------------------------------------------------------------------------
            */

            $queue->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Re-throw
            |--------------------------------------------------------------------------
            |
            | Penting agar Laravel tetap mengetahui bahwa
            | job mengalami kegagalan.
            |
            */

            throw $e;
        }
    }
}