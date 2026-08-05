<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderAdminService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderAdminService $adminService,
    ) {}

    public function index(Request $request)
    {
        $orders = $this->adminService->list($request);
        $stats = $this->adminService->stats();
        $statis = $this->adminService->statistics();

        return view(
            'admin.modules.order.index',
            compact('orders', 'stats', 'statis')
        );
    }

    public function show(string $id)
    {
        $order = $this->adminService->show($id);

        return view(
            'admin.modules.order.show',
            compact('order')
        );
    }

    public function processing(Order $order)
    {
        $order = $this->adminService->changeStatus(
            order: $order,
            status: 'processing',
            description: 'Pesanan mulai diproses'
        );

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil diproses.',
            'data' => $order,
        ]);
    }
}