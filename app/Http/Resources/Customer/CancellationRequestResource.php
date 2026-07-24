<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancellationRequestResource extends JsonResource
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
            | Cancellation
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,
            'reason' => $this->reason,
            'note' => $this->note,

            /*
            |--------------------------------------------------------------------------
            | Admin
            |--------------------------------------------------------------------------
            */

            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,

            /*
            |--------------------------------------------------------------------------
            | Refund
            |--------------------------------------------------------------------------
            */

            'refund_status' => $this->refund_status,
            'refund_note' => $this->refund_note,
            'refunded_at' => $this->refunded_at,

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}