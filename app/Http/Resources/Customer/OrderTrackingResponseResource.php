<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTrackingResponseResource extends JsonResource
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
            | Response
            |--------------------------------------------------------------------------
            */

            'success' => true,
            'message' => 'Data tracking berhasil diambil.',

            /*
            |--------------------------------------------------------------------------
            | Data
            |--------------------------------------------------------------------------
            */

            'data' => [
                /*
                |--------------------------------------------------------------------------
                | Order
                |--------------------------------------------------------------------------
                */

                'order' => new OrderTrackingResource($this['order']),

                /*
                |--------------------------------------------------------------------------
                | Customer Actions
                |--------------------------------------------------------------------------
                */
                'actions' => $this['actions'] ?? [],
            ],
        ];
    }
}