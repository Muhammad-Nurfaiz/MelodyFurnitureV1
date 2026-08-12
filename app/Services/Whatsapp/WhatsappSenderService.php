<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;

class WhatsappSenderService
{
    /**
     * Mengirim pesan WhatsApp melalui WAHA.
     */
    public function send(
        string $phone,
        string $message,
    ): void {
        $phone = $this->normalizePhoneNumber($phone);

        Http::withHeaders([
            'X-Api-Key' => config('services.waha.api_key'),
            'Accept' => 'application/json',
        ])
            ->post(
                rtrim(config('services.waha.url'), '/') . '/api/sendText',
                [
                    'session' => config('services.waha.session', 'default'),
                    'chatId' => $phone . '@c.us',
                    'text' => $message,
                ],
            )
            ->throw();
    }

    /**
     * Normalisasi nomor WhatsApp Indonesia.
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}