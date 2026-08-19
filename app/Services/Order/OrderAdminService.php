<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(
        protected OrderQueryService $queries,
        protected OrderWorkflowService $workflow,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Order List
    |--------------------------------------------------------------------------
    */
    public function list(Request $request): LengthAwarePaginator {
        return $this->paginate($request);
    }

    public function paginate(Request $request,int $perPage = 15): LengthAwarePaginator {
        $query = $this->queries->query();
        $query = $this->queries->search($query,$request->input('search'));
        $query = $this->queries->filterStatus($query,$request->input('status'));
        $query = $this->queries->filterPaymentStatus($query,$request->input('payment_status'));
        $query = $this->queries->filterCourier($query,$request->input('courier'));
        $query = $this->queries->filterDate($query,$request->input('date'));
        $query = $this->queries->sort($query,$request->input('sort', 'created_at'),$request->input('direction', 'desc'));
        return $this->queries->paginate($query,$perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    public function detail(string $id): Order {
        return $this->queries->find($id)?? abort(404);
    }

    /*
    |--------------------------------------------------------------------------
    | Change Status
    |--------------------------------------------------------------------------
    */

    public function changeStatus(Order $order,string $status,?string $description = null,?string $adminId = null): Order {
        return $this->workflow->changeStatus($order,$status,$description,$adminId);
    }

    /*
    |--------------------------------------------------------------------------
    | Available Actions
    |--------------------------------------------------------------------------
    */

    public function availableActions(Order $order): array {
        return $this->workflow->availableTransitions($order);
    }

    public function show(string $id): Order
    {
        $order = $this->queries->find($id);

        if (! $order) {
            abort(404);
        }

        $order->loadMissing(['cancellationRequest',]);

        $order->action = $this->action($order);

        return $order;
    }

    public function action(Order $order): array
    {
        return match ($order->status) {

            'pending' => [
                'type' => 'info',
                'button_variant' => null,
                'label' => 'Menunggu Pembayaran',
                'route' => null,
                'method' => null,
            ],

            'paid' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => 'Proses Pesanan',
                'route' => route(
                    'admin.orders.processing',
                    $order
                ),
                'method' => 'PATCH',
            ],

            'processing' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => $order->shipment
                    ? 'Pickup Kurir'
                    : 'Buat Pengiriman',
                'route' => $order->shipment
                    ? route(
                        'admin.shipments.pickup',
                        $order->shipment
                    )
                    : route(
                        'admin.shipments.store',
                        $order
                    ),
                'method' => $order->shipment
                    ? 'PATCH'
                    : 'POST',
            ],

            'picked_up' => [
                'type' => 'primary',
                'button_variant' => 'primary',
                'label' => 'Tandai Dikirim',
                'route' => route(
                    'admin.shipments.transit',
                    $order->shipment
                ),
                'method' => 'PATCH',
            ],

            'shipped' => [
                'type' => 'success',
                'button_variant' => 'success',
                'label' => 'Selesaikan Pesanan',
                'route' => route(
                    'admin.shipments.delivered',
                    $order->shipment
                ),
                'method' => 'PATCH',
            ],

            'completed' => [
                'type' => 'success',
                'button_variant' => null,
                'label' => 'Pesanan Selesai',
                'route' => null,
                'method' => null,
            ],

            'cancelled' => [
                'type' => 'danger',
                'button_variant' => null,
                'label' => 'Pesanan Dibatalkan',
                'route' => null,
                'method' => null,
            ],

            'req_cancel' => [
                'type' => 'warning',
                'button_variant' => null,
                'label' => 'Permintaan Cancel',
                'route' => null,
                'method' => null,
            ],

            default => [
                'type' => 'gray',
                'button_variant' => null,
                'label' => '-',
                'route' => null,
                'method' => null,
            ],
        };
    }

    public function statistics(): array
    {
        return $this->queries->statistics();
    }

    public function stats(): array
    {
        return $this->queries->stats();
    }
}