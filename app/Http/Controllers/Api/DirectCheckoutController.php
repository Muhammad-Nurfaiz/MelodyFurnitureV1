<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DirectCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Cart\DirectCheckoutService;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use App\Services\Voucher\VoucherService;
use Illuminate\Http\JsonResponse;
use MadeByClowd\Nusantara\Models\Regency;
use RuntimeException;

class DirectCheckoutController extends Controller
{
    public function __construct(
        protected DirectCheckoutService $directCheckoutService,
        protected OrderService          $orderService,
        protected VoucherService        $voucherService,
        protected CustomerService       $customerService,
    ) {}

    /**
     * Direct Checkout
     *
     * Checkout langsung dari halaman detail produk tanpa melalui cart.
     * Produk dikirim sebagai array items[] di body request.
     * Cart yang ada tidak tersentuh — tidak ada item yang dihapus dari cart.
     */
    public function store(DirectCheckoutRequest $request): JsonResponse
    {
        $payload = $request->payload();

        /*
        |--------------------------------------------------------------------------
        | Resolve Products from Payload
        |--------------------------------------------------------------------------
        |
        | Ubah array [{product_id, quantity}] menjadi Collection
        | yang bisa diproses oleh OrderService.
        |
        */

        $products = $this->directCheckoutService->resolveItems(
            $payload['items']
        );

        /*
        |--------------------------------------------------------------------------
        | Checkout Customer
        |--------------------------------------------------------------------------
        |
        | Customer ditentukan berdasarkan nomor telepon.
        | - Sudah ada → pakai customer existing.
        | - Belum ada → buat customer baru.
        |
        */

        $customer = $this->customerService->resolveCheckoutCustomer([
            'name'    => $payload['name'],
            'email' => $payload['email'] ?? null,
            'phone'   => $payload['phone'],
            'address' => $payload['shipping_address']['address'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cart Customer (Guest)
        |--------------------------------------------------------------------------
        |
        | Direct checkout tidak punya cart.
        | Gunakan customer yang sama sebagai cartCustomer.
        | OrderService tidak akan menghapus cart karena selectedItemIds = null
        | dan direct checkout melewati clearCart.
        |
        */

        $cartCustomer = $request->attributes->get('customer');

        /*
        |--------------------------------------------------------------------------
        | Voucher
        |--------------------------------------------------------------------------
        */

        $voucher = null;

        if (!empty($payload['voucher_code'])) {
            $voucher = $this->voucherService->findByCode(
                $payload['voucher_code']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve Regency
        |--------------------------------------------------------------------------
        */

        $regency = Regency::query()
            ->with('province')
            ->find($payload['shipping_address']['regency_id']);

        if (!$regency) {
            throw new RuntimeException(
                'Kabupaten/Kota pengiriman tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Shipping Address
        |--------------------------------------------------------------------------
        |
        | city dan province selalu diambil dari database Nusantara,
        | bukan dari frontend.
        |
        */

        $shippingAddress = [
            'recipient_name' =>
                $payload['shipping_address']['recipient_name'],

            'phone' =>
                $payload['shipping_address']['phone'],

            'regency_id' =>
                $regency->id,

            'city' =>
                $regency->name,

            'province' =>
                $regency->province->name,

            'address' =>
                $payload['shipping_address']['address'],

            'area' =>
                $payload['shipping_address']['area'],

            'postal_code' =>
                $payload['shipping_address']['postal_code'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        $shipping = [
            'courier' => $payload['courier'],
            'service' => 'regular',
            'address' => $shippingAddress,
        ];

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        |
        | selectedItemIds = null → cart tidak akan dikosongkan.
        | Direct checkout tidak memiliki cart yang perlu dibersihkan.
        |
        */

        $order = $this->orderService->checkout(
            customer:        $customer,
            cartCustomer:    $cartCustomer,
            products:        $products,
            voucher:         $voucher,
            shipping:        $shipping,
            selectedItemIds: null,
            clearCart: false,
        );

        return response()->json([
            'message' => 'Checkout berhasil.',
            'data'    => new OrderResource($order),
        ], 201);
    }
}
