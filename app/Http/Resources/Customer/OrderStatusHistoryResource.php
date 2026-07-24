<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,

            'title' => $this->title(),

            'description' =>

                $this->description
                ?: $this->defaultDescription(),

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at,

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */

    protected function title(): string
    {

        return match ($this->status) {

            'pending'
                => 'Pesanan dibuat',

            'paid'
                => 'Pembayaran diterima',

            'processing'
                => 'Pesanan diproses',

            'picked_up'
                => 'Pesanan dijemput ekspedisi',

            'completed'
                => 'Pesanan selesai',

            'req_cancel'
                => 'Pengajuan pembatalan',

            'cancelled'
                => 'Pesanan dibatalkan',

            default
                => ucfirst($this->status),

        };

    }

    /*
    |--------------------------------------------------------------------------
    | Default Description
    |--------------------------------------------------------------------------
    */

    protected function defaultDescription(): string
    {

        return match ($this->status) {

            'pending'
                => 'Pesanan berhasil dibuat.',

            'paid'
                => 'Pembayaran berhasil diterima.',

            'processing'
                => 'Pesanan sedang disiapkan oleh Melody Furniture.',

            'picked_up'
                => 'Pesanan telah dijemput oleh ekspedisi.',

            'completed'
                => 'Pesanan telah diterima pelanggan.',

            'req_cancel'
                => 'Pelanggan mengajukan pembatalan pesanan.',

            'cancelled'
                => 'Pesanan telah dibatalkan.',

            default
                => '',

        };

    }
}