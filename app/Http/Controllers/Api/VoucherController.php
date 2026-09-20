<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VoucherCheckRequest;
use App\Services\Voucher\VoucherService;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Check Voucher
    |--------------------------------------------------------------------------
    |
    | Endpoint publik untuk validasi kode voucher secara mandiri
    | sebelum user melakukan submit checkout.
    |
    | Response sukses  → valid: true  + detail diskon
    | Response gagal   → valid: false + pesan alasan
    |
    */

    public function check(VoucherCheckRequest $request): JsonResponse
    {
        $code     = $request->validated('code');
        $subtotal = (float) $request->validated('subtotal');

        /*
        |--------------------------------------------------------------------------
        | Find Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = $this->voucherService->findByCode($code);

        if (!$voucher) {
            return response()->json([
                'valid'   => false,
                'message' => 'Voucher tidak tersedia.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Voucher
        |--------------------------------------------------------------------------
        |
        | Gunakan isValid() agar tidak throw exception,
        | lalu tangkap pesan spesifik lewat validate() jika tidak valid.
        |
        */

        if (!$this->voucherService->isValid($voucher, $subtotal)) {

            $message = 'Voucher tidak dapat digunakan.';

            try {
                $this->voucherService->validate($voucher, $subtotal);
            } catch (\Throwable $e) {
                $message = $e->getMessage();
            }

            return response()->json([
                'valid'   => false,
                'message' => $message,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate Discount
        |--------------------------------------------------------------------------
        */

        $estimatedDiscount = $this->voucherService->calculateDiscount(
            $voucher,
            $subtotal
        );

        return response()->json([
            'valid'              => true,
            'message'            => 'Voucher berhasil diterapkan.',
            'code'               => $voucher->code,
            'discount_type'      => $voucher->discount_type,
            'discount_value'     => $voucher->discount_value,
            'max_discount_amount'=> $voucher->max_discount_amount,
            'min_order_amount'   => $voucher->min_order_amount,
            'estimated_discount' => $estimatedDiscount,
        ]);
    }
}
