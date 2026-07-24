<?php

namespace App\Services\Payment;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Refund;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use App\Services\Payment\RefundNumberService;

class RefundService
{
    public function __construct(
        protected RefundNumberService $numberService,
    ) {}
    /*
    |--------------------------------------------------------------------------
    | Create Refund
    |--------------------------------------------------------------------------
    */

    public function create(
        Order $order
    ): Refund {

        if ($order->refund) {

            throw new RuntimeException(
                'Refund sudah pernah dibuat.'
            );

        }

        return Refund::create([

            'refund_number'
                => $this->numberService->generate(),

            'order_id'
                => $order->id,

            'payment_id'
                => $order->payment->id,

            'amount'
                => $order->total_payment,

            'status'
                => 'pending',

            'requested_at'
                => now(),

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Processing Refund
    |--------------------------------------------------------------------------
    */

    public function start(
        Refund $refund,
        Admin $admin
    ): Refund {

        if ($refund->status !== 'pending') {

            throw new RuntimeException(
                'Refund tidak dapat diproses.'
            );

        }

        $refund->update([

            'status' => 'processing',

            'processed_by' => $admin->id,

            'processed_at' => now(),

        ]);

        return $refund->fresh();

    }

    /*
    |--------------------------------------------------------------------------
    | Complete Refund
    |--------------------------------------------------------------------------
    */

    public function complete(
        Refund $refund,
        Admin $admin,
        ?string $notes = null
    ): Refund {

        if (

            ! in_array(

                $refund->status,

                [

                    'pending',

                    'processing',

                ]

            )

        ) {

            throw new RuntimeException(
                'Refund tidak dapat diselesaikan.'
            );

        }

        $refund->update([

            'status' => 'completed',

            'processed_by' => $admin->id,

            'notes' => $notes,

            'completed_at' => now(),

        ]);

        return $refund->fresh();

    }

    /*
    |--------------------------------------------------------------------------
    | Reject Refund
    |--------------------------------------------------------------------------
    */

    public function reject(
        Refund $refund,
        Admin $admin,
        string $notes
    ): Refund {

        if ($refund->status !== 'pending') {

            throw new RuntimeException(
                'Refund tidak dapat ditolak.'
            );

        }

        $refund->update([

            'status' => 'rejected',

            'processed_by' => $admin->id,

            'notes' => $notes,

            'processed_at' => now(),

        ]);

        return $refund->fresh();

    }
}