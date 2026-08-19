<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardAnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalyticsService $analyticsService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Date Filter
        |--------------------------------------------------------------------------
        |
        | Untuk tahap awal kita menerima:
        |
        | ?start_date=2026-08-01
        | ?end_date=2026-08-31
        |
        | Jika tidak diberikan, analytics akan menggunakan seluruh data.
        |
        */

        $startDate = $request->input('start_date');

        $endDate = $request->input('end_date');

        /*
        |--------------------------------------------------------------------------
        | Analytics
        |--------------------------------------------------------------------------
        */

        $summary = $this->analyticsService->summary(
            startDate: $startDate,
            endDate: $endDate,
        );

        $orderStatus = $this->analyticsService->orderStatusSummary(
            startDate: $startDate,
            endDate: $endDate,
        );

        $topSellingProducts =
            $this->analyticsService->topSellingProducts(
                limit: 5,
                startDate: $startDate,
                endDate: $endDate,
            );

        $salesTrend = $this->analyticsService->salesTrend(
            startDate: $startDate,
            endDate: $endDate,
        );

        $salesByCategory =
            $this->analyticsService->salesByCategory(
                startDate: $startDate,
                endDate: $endDate,
            );

        $recentOrders =
            $this->analyticsService->recentOrders(
                limit: 5
            );

        $refundSummary =
            $this->analyticsService->refundSummary(
                startDate: $startDate,
                endDate: $endDate,
            );

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view('admin.modules.dashboard.index', [

            'summary' =>
                $summary,

            'orderStatus' =>
                $orderStatus,

            'topSellingProducts' =>
                $topSellingProducts,

            'salesTrend' =>
                $salesTrend,

            'salesByCategory' =>
                $salesByCategory,

            'recentOrders' =>
                $recentOrders,

            'refundSummary' =>
                $refundSummary,

            /*
            |----------------------------------------------------------------------
            | Filter State
            |----------------------------------------------------------------------
            */

            'startDate' =>
                $startDate,

            'endDate' =>
                $endDate,
        ]);
    }
}