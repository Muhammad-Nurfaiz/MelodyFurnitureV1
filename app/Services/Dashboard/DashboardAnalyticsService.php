<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /*
    |--------------------------------------------------------------------------
    | Sales Order Scope
    |--------------------------------------------------------------------------
    |
    | Order yang dianggap sebagai penjualan:
    |
    | - payment_status = paid
    | - status bukan cancelled
    |
    | Data produk terjual selalu berasal dari order_items.
    | products.total_sold TIDAK digunakan.
    |
    */

    protected function salesOrderQuery()
    {
        return Order::query()
            ->where('payment_status', 'paid')
            ->where('status', '!=', 'cancelled');
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */

    public function summary(
        ?string $startDate = null,
        ?string $endDate = null
    ): array {

        /*
        |----------------------------------------------------------------------
        | Sales Orders
        |----------------------------------------------------------------------
        */

        $orders = $this->salesOrderQuery()
            ->when(
                $startDate,
                fn ($query) =>
                    $query->whereDate('paid_at', '>=', $startDate)
            )
            ->when(
                $endDate,
                fn ($query) =>
                    $query->whereDate('paid_at', '<=', $endDate)
            );

        /*
        |----------------------------------------------------------------------
        | Total Orders
        |----------------------------------------------------------------------
        */

        $totalOrders = (clone $orders)->count();

        /*
        |----------------------------------------------------------------------
        | Gross Revenue
        |----------------------------------------------------------------------
        |
        | Menggunakan total_payment dari order yang sudah dibayar.
        |
        */

        $grossRevenue = (clone $orders)
            ->sum('total_payment');

        /*
        |----------------------------------------------------------------------
        | Total Product Sold
        |----------------------------------------------------------------------
        |
        | TIDAK menggunakan products.total_sold.
        |
        | Menggunakan SUM(order_items.quantity).
        |
        */

        $totalProductsSold = OrderItem::query()
            ->whereHas(
                'order',
                function ($query) use ($startDate, $endDate) {

                    $query
                        ->where('payment_status', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->when(
                            $startDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '>=',
                                    $startDate
                                )
                        )
                        ->when(
                            $endDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '<=',
                                    $endDate
                                )
                        );
                }
            )
            ->sum('quantity');

        /*
        |----------------------------------------------------------------------
        | Completed Refund
        |----------------------------------------------------------------------
        */

        $completedRefund = Refund::query()
            ->where('status', 'completed')
            ->when(
                $startDate,
                fn ($query) =>
                    $query->whereDate(
                        'completed_at',
                        '>=',
                        $startDate
                    )
            )
            ->when(
                $endDate,
                fn ($query) =>
                    $query->whereDate(
                        'completed_at',
                        '<=',
                        $endDate
                    )
            )
            ->sum('amount');

        /*
        |----------------------------------------------------------------------
        | Net Revenue
        |----------------------------------------------------------------------
        */

        $netRevenue = $grossRevenue - $completedRefund;

        return [
            'total_orders' => $totalOrders,

            'total_products_sold' => $totalProductsSold,

            'gross_revenue' => $grossRevenue,

            'completed_refund' => $completedRefund,

            'net_revenue' => $netRevenue,

            'total_products' => Product::count(),

            'total_categories' => Category::count(),

            'ready_stock_products' =>
                Product::where('ready_stock', '>', 0)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Order Status Summary
    |--------------------------------------------------------------------------
    */

    public function orderStatusSummary(
        ?string $startDate = null,
        ?string $endDate = null
    ): array {

        $query = Order::query()
            ->when(
                $startDate,
                fn ($query) =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $startDate
                    )
            )
            ->when(
                $endDate,
                fn ($query) =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $endDate
                    )
            );

        return [
            'pending' =>
                (clone $query)
                    ->where('status', 'pending')
                    ->count(),

            'paid' =>
                (clone $query)
                    ->where('status', 'paid')
                    ->count(),

            'processing' =>
                (clone $query)
                    ->where('status', 'processing')
                    ->count(),

            'picked_up' =>
                (clone $query)
                    ->where('status', 'picked_up')
                    ->count(),

            'shipped' =>
                (clone $query)
                    ->where('status', 'shipped')
                    ->count(),

            'completed' =>
                (clone $query)
                    ->where('status', 'completed')
                    ->count(),

            'cancelled' =>
                (clone $query)
                    ->where('status', 'cancelled')
                    ->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Top Selling Products
    |--------------------------------------------------------------------------
    |
    | SUM(quantity) berasal dari order_items.
    |
    | product_name dan product_sku menggunakan snapshot transaksi.
    |
    */

    public function topSellingProducts(
        int $limit = 5,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = OrderItem::query()
            ->join(
                'products',
                'products.id',
                '=',
                'order_items.product_id'
            )
            ->select([
                'order_items.product_id',
                'products.sku as product_sku',
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_sales'),
            ])
            ->whereHas('order', function ($query) {
                $query
                    ->where('payment_status', 'paid')
                    ->where('status', '!=', 'cancelled');
            });

        if ($startDate) {
            $query->whereDate('order_items.created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('order_items.created_at', '<=', $endDate);
        }

        return $query
            ->groupBy(
                'order_items.product_id',
                'products.sku',
                'order_items.product_name'
            )
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Sales Trend
    |--------------------------------------------------------------------------
    |
    | Trend penjualan menggunakan tanggal paid_at.
    |
    | Setiap tanggal:
    |
    | - jumlah order
    | - jumlah produk terjual
    | - revenue
    |
    */

    public function salesTrend(
        ?string $startDate = null,
        ?string $endDate = null
    ) {

        $orders = $this->salesOrderQuery()
            ->when(
                $startDate,
                fn ($query) =>
                    $query->whereDate(
                        'paid_at',
                        '>=',
                        $startDate
                    )
            )
            ->when(
                $endDate,
                fn ($query) =>
                    $query->whereDate(
                        'paid_at',
                        '<=',
                        $endDate
                    )
            )
            ->select([
                DB::raw('DATE(paid_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_payment) as revenue'),
            ])
            ->groupBy(
                DB::raw('DATE(paid_at)')
            )
            ->orderBy('date')
            ->get();

        /*
        |----------------------------------------------------------------------
        | Product Quantity Per Day
        |----------------------------------------------------------------------
        */

        $quantities = OrderItem::query()
            ->whereHas(
                'order',
                function ($query) use ($startDate, $endDate) {

                    $query
                        ->where('payment_status', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->when(
                            $startDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '>=',
                                    $startDate
                                )
                        )
                        ->when(
                            $endDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '<=',
                                    $endDate
                                )
                        );
                }
            )
            ->join(
                'orders',
                'orders.id',
                '=',
                'order_items.order_id'
            )
            ->select([
                DB::raw('DATE(orders.paid_at) as date'),
                DB::raw(
                    'SUM(order_items.quantity) as total_products_sold'
                ),
            ])
            ->groupBy(
                DB::raw('DATE(orders.paid_at)')
            )
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        /*
        |----------------------------------------------------------------------
        | Merge
        |----------------------------------------------------------------------
        */

        return $orders->map(function ($row) use ($quantities) {

            $quantity = $quantities->get($row->date);

            return [
                'date' =>
                    $row->date,

                'total_orders' =>
                    (int) $row->total_orders,

                'total_products_sold' =>
                    (int) ($quantity?->total_products_sold ?? 0),

                'revenue' =>
                    (float) $row->revenue,
            ];
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Sales By Category
    |--------------------------------------------------------------------------
    |
    | Produk terjual berdasarkan kategori produk.
    |
    | Quantity tetap berasal dari order_items.
    |
    */

    public function salesByCategory(
        ?string $startDate = null,
        ?string $endDate = null
    ) {

        return OrderItem::query()
            ->select([
                'products.category_id',
                'categories.name as category_name',

                DB::raw(
                    'SUM(order_items.quantity) as total_quantity'
                ),

                DB::raw(
                    'SUM(order_items.subtotal) as total_sales'
                ),
            ])
            ->join(
                'products',
                'products.id',
                '=',
                'order_items.product_id'
            )
            ->leftJoin(
                'categories',
                'categories.id',
                '=',
                'products.category_id'
            )
            ->whereHas(
                'order',
                function ($query) use ($startDate, $endDate) {

                    $query
                        ->where('payment_status', 'paid')
                        ->where('status', '!=', 'cancelled')
                        ->when(
                            $startDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '>=',
                                    $startDate
                                )
                        )
                        ->when(
                            $endDate,
                            fn ($query) =>
                                $query->whereDate(
                                    'paid_at',
                                    '<=',
                                    $endDate
                                )
                        );
                }
            )
            ->groupBy(
                'products.category_id',
                'categories.name'
            )
            ->orderByDesc('total_quantity')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Orders
    |--------------------------------------------------------------------------
    */

    public function recentOrders(
        int $limit = 5
    ) {
        return Order::query()
            ->with([
                'customer',
                'payment',
            ])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Refund Summary
    |--------------------------------------------------------------------------
    */

    public function refundSummary(
        ?string $startDate = null,
        ?string $endDate = null
    ): array {

        $query = Refund::query()
            ->when(
                $startDate,
                fn ($query) =>
                    $query->whereDate(
                        'requested_at',
                        '>=',
                        $startDate
                    )
            )
            ->when(
                $endDate,
                fn ($query) =>
                    $query->whereDate(
                        'requested_at',
                        '<=',
                        $endDate
                    )
            );

        return [
            'total' =>
                (clone $query)->count(),

            'pending' =>
                (clone $query)
                    ->where('status', 'pending')
                    ->count(),

            'processing' =>
                (clone $query)
                    ->where('status', 'processing')
                    ->count(),

            'completed' =>
                (clone $query)
                    ->where('status', 'completed')
                    ->count(),

            'rejected' =>
                (clone $query)
                    ->where('status', 'rejected')
                    ->count(),

            'completed_amount' =>
                (clone $query)
                    ->where('status', 'completed')
                    ->sum('amount'),
        ];
    }
}