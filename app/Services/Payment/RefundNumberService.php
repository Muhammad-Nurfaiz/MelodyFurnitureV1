<?php

namespace App\Services\Payment;

use App\Models\Refund;

class RefundNumberService
{
    public function generate(): string
    {
        $date = now()->format('Ymd');

        $last = Refund::query()
            ->whereDate(
                'created_at',
                today()
            )
            ->latest('id')
            ->first();

        $number = 1;

        if ($last) {

            $number = intval(
                substr(
                    $last->refund_number,
                    -5
                )
            ) + 1;

        }

        return sprintf(

            'RFD-%s-%05d',

            $date,

            $number

        );
    }
}