<?php

namespace App\Services\Whatsapp;

use App\Models\WhatsappQueue;

class WhatsappGatewayService
{
    /**
     * Send WhatsApp message through gateway.
     *
     * Untuk sementara method ini belum benar-benar
     * melakukan HTTP request ke WAHA.
     */
    public function send(WhatsappQueue $queue): bool
    {
        /*
        |--------------------------------------------------------------------------
        | STEP BERIKUTNYA
        |--------------------------------------------------------------------------
        |
        | Di sini nantinya kita akan melakukan request:
        |
        | Laravel
        |    ↓
        | WAHA API
        |    ↓
        | WhatsApp
        |
        */

        return false;
    }
}