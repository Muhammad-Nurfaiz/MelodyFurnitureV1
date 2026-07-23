<?php

namespace App\Services\Order;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OrderQueryService
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    public function query(): Builder
    {
        return Order::query()->with([
            'customer',
            'payment',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    public function paginate(
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->query()
            ->latest()
            ->paginate($perPage);
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(
        int $id
    ): ?Order {
        return $this->query()
            ->with([
                'items.product',
                'statusHistories',
            ])
            ->find($id);
    }

    public function findByNumber(
        string $number
    ): ?Order {
        return $this->query()
            ->where(
                'order_number',
                $number
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function customerOrders(
        Customer $customer
    ): Builder {
        return $this->query()
            ->where(
                'customer_id',
                $customer->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Scope
    |--------------------------------------------------------------------------
    */

    public function pending(): Builder
    {
        return $this->status('pending');
    }

    public function paid(): Builder
    {
        return $this->status('paid');
    }

    public function processing(): Builder
    {
        return $this->status('processing');
    }

    public function pickedUp(): Builder
    {
        return $this->status('picked_up');
    }

    public function completed(): Builder
    {
        return $this->status('completed');
    }

    public function cancelled(): Builder
    {
        return $this->status('cancelled');
    }

    protected function status(
        string $status
    ): Builder {
        return $this->query()
            ->where(
                'status',
                $status
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    public function paymentPending(): Builder
    {
        return $this->query()
            ->where(
                'payment_status',
                'pending'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function search(
        Builder $query,
        ?string $keyword
    ): Builder {
        if (blank($keyword)) {
            return $query;
        }
        return $query->where(function ($q) use ($keyword) {
            $q->where(
                'order_number',
                'like',
                "%{$keyword}%"
            )
            ->orWhereHas(
                'customer',
                fn ($customer) => $customer->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                )
            );
        });
    }
}