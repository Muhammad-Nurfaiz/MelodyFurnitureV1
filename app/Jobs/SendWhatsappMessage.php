<?php

namespace App\Jobs;

use App\Models\WhatsappQueue;
use App\Services\Whatsapp\WhatsappSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SendWhatsappMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Maksimal attempt job.
     *
     * Karena middleware dapat melakukan release,
     * attempt bisa bertambah walaupun pesan belum dikirim.
     */
    public int $tries = 10;

    public function __construct(
        public string $whatsappQueueId,
    ) {}

    /**
     * Mencegah dua pesan WhatsApp dikirim bersamaan.
     *
     * Lock bersifat global untuk seluruh pengiriman WhatsApp,
     * bukan berdasarkan queue ID.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('whatsapp-send'))
                ->shared()
                ->releaseAfter(5)
                ->expireAfter(120),
        ];
    }

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

            /*
            |--------------------------------------------------------------------------
            | Delay Antar Pesan
            |--------------------------------------------------------------------------
            |
            | Lock WithoutOverlapping masih aktif selama job berjalan.
            |
            | Jadi setelah pesan berhasil dikirim, worker menunggu
            | 20 detik sebelum lock dilepas dan job WhatsApp berikutnya
            | diperbolehkan berjalan.
            |
            */

            sleep(20);

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
            */

            throw $e;
        }
    }
}