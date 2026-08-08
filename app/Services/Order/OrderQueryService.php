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

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function stats(): array
    {
        return [
            'total' => Order::query()->count(),

            'pending_payment' => Order::query()
                ->where('payment_status', 'pending')
                ->count(),

            'processing' => Order::query()
                ->where('status', 'processing')
                ->count(),

            'completed' => Order::query()
                ->where('status', 'completed')
                ->count(),
        ];
    }

    public function statistics(): array
    {
        return [
            'total' => Order::query()->count(),

            'pending_payment' => Order::query()
                ->where('payment_status', 'pending')
                ->count(),

            'paid' => Order::query()
                ->where('payment_status', 'paid')
                ->count(),

            'processing' => Order::query()
                ->where('status', 'processing')
                ->count(),

            'completed' => Order::query()
                ->where('status', 'completed')
                ->count(),

            'cancelled' => Order::query()
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    public function query(): Builder {
        return Order::query()
            ->with([
                'payment',
                'shipment',
            ]);
    }

    public function detail(): Builder {
        return $this->query()
            ->with([
                'items.product',
                'statusHistories',
                'cancellationRequest',
                'shipment',
                'refund',
            ]);
    }

    public function requestCancel(): Builder {
        return $this->status('req_cancel');
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    public function paginate(Builder $query,int $perPage = 15): LengthAwarePaginator {
        return $query->paginate($perPage)->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(string $id): ?Order {
        return $this->detail()->find($id);
    }

    public function findByNumber(string $number): ?Order {
        return $this->detail()->where('order_number',$number)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Tracking Token
    |--------------------------------------------------------------------------
    */
    public function findByTrackingToken(string $trackingToken): ?Order {
        return $this->detail()->where('tracking_token',$trackingToken)->first();
    }

    public function byTrackingToken(string $trackingToken): Builder {
        return $this->query()->where('tracking_token',$trackingToken);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    public function customerOrders(Customer $customer): Builder {
        return $this->detail()->where('customer_id',$customer->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Scope
    |--------------------------------------------------------------------------
    */

    public function pending(): Builder {
        return $this->status('pending');
    }

    public function paid(): Builder {
        return $this->status('paid');
    }

    public function processing(): Builder {
        return $this->status('processing');
    }

    public function pickedUp(): Builder {
        return $this->status('picked_up');
    }

    public function completed(): Builder {
        return $this->status('completed');
    }

    public function cancelled(): Builder {
        return $this->status('cancelled');
    }

    protected function status(string $status): Builder {
        return $this->query()->where('status', $status);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    public function paymentPending(): Builder {
        return $this->query()->where('payment_status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function search(Builder $query,?string $keyword): Builder {

        if (blank($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {

            $q->where(
                'order_number',
                'like',
                "%{$keyword}%"
            )

            ->orWhere(
                'tracking_token',
                'like',
                "%{$keyword}%"
            )

            ->orWhere(
                'customer_name',
                'like',
                "%{$keyword}%"
            )

            ->orWhere(
                'customer_email',
                'like',
                "%{$keyword}%"
            )

            ->orWhere(
                'customer_phone',
                'like',
                "%{$keyword}%"
            )

            ->orWhere(
                'tracking_number',
                'like',
                "%{$keyword}%"
            );
        });
    }

    public function filterStatus(Builder $query, ?string $status): Builder {
        if (blank($status) || $status === 'all') {
            return $query;
        }
        return $query->where('status', $status);
    }

    public function filterPaymentStatus(Builder $query,?string $status): Builder {
        if (blank($status) || $status === 'all') {
            return $query;
        }
        return $query->where('payment_status', $status);
    }

    public function filterCourier(Builder $query,?string $courier): Builder {
        if (blank($courier) || $courier === 'all') {
            return $query;
        }
        return $query->where('courier', $courier);
    }

    public function filterDate(Builder $query,?string $from,?string $to): Builder {
        if ($from) {
            $query->whereDate('created_at','>=',$from);
        }
        if ($to) {
            $query->whereDate('created_at','<=',$to);
        }
        return $query;
    }

    public function sort(Builder $query,string $column = 'created_at',string $direction = 'desc'): Builder {
        return $query->orderBy($column,$direction);
    }
}