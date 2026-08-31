<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CancellationRequestResource extends JsonResource
{
    /**
     * Transform resource into an array.
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

            'previous_status' => $this->previous_status,

            /*
            |--------------------------------------------------------------------------
            | Admin Decision
            |--------------------------------------------------------------------------
            */

            'approved_by' => $this->approved_by,

            'approved_at' => $this->approved_at,

            'rejected_by' => $this->rejected_by,

            'rejected_at' => $this->rejected_at,

            'admin_notes' => $this->admin_notes,

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