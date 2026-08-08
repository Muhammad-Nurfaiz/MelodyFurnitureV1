<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends AdminController
{
    /*
    |--------------------------------------------------------------------------
    | Customer List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $customers = Customer::query()
            ->withCount('orders')

            ->withSum([
                'orders as total_spending' => function ($query) {
                    $query->whereNotIn('status', ['cancelled']);
                },
            ], 'total_payment')

            ->withMax('orders', 'created_at')

            ->when($search !== '', function ($query) use ($search) {

                $query->where(function ($query) use ($search) {

                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");

                });

            })

            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.modules.customer.index',
            compact(
                'customers',
                'search',
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Detail
    |--------------------------------------------------------------------------
    */

    public function show(Customer $customer)
    {
        $orders = $customer->orders()
            ->latest()
            ->paginate(10);

        $totalOrders = $customer->orders()->count();

        $totalSpending = $customer->orders()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_payment');

        $lastOrder = $customer->orders()
            ->latest()
            ->first();

        return view(
            'admin.modules.customer.show',
            compact(
                'customer',
                'orders',
                'totalOrders',
                'totalSpending',
                'lastOrder'
            )
        );
    }
}