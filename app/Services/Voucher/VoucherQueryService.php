<?php

namespace App\Services\Voucher;

use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VoucherQueryService
{
    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    public function query(): Builder
    {
        return Voucher::query();
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    public function paginate(
        Builder $query,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Find
    |--------------------------------------------------------------------------
    */

    public function find(string $id): ?Voucher
    {
        return $this->query()->find($id);
    }

    public function findByCode(string $code): ?Voucher
    {
        return $this->query()
            ->where('code', $code)
            ->first();
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

        return $query->where(
            'code',
            'like',
            "%{$keyword}%"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filter - Active Status
    |--------------------------------------------------------------------------
    */

    public function filterStatus(
        Builder $query,
        ?string $status
    ): Builder {
        if (blank($status) || $status === 'all') {
            return $query;
        }

        return match ($status) {
            'active' => $query->where('is_active', true),

            'inactive' => $query->where('is_active', false),

            default => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Filter - Discount Type
    |--------------------------------------------------------------------------
    */

    public function filterDiscountType(
        Builder $query,
        ?string $type
    ): Builder {
        if (blank($type) || $type === 'all') {
            return $query;
        }

        return $query->where(
            'discount_type',
            $type
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filter - Expired
    |--------------------------------------------------------------------------
    */

    public function filterExpired(
        Builder $query,
        ?string $expired
    ): Builder {
        if (blank($expired) || $expired === 'all') {
            return $query;
        }

        return match ($expired) {
            'expired' => $query->where(
                'expiry_date',
                '<',
                now()
            ),

            'active' => $query->where(
                'expiry_date',
                '>=',
                now()
            ),

            default => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Filter - Usage
    |--------------------------------------------------------------------------
    */

    public function filterUsage(
        Builder $query,
        ?string $usage
    ): Builder {
        if (blank($usage) || $usage === 'all') {
            return $query;
        }

        return match ($usage) {
            'unlimited' => $query->whereNull(
                'usage_limit'
            ),

            'available' => $query
                ->whereNotNull('usage_limit')
                ->whereColumn(
                    'used_count',
                    '<',
                    'usage_limit'
                ),

            'limit_reached' => $query
                ->whereNotNull('usage_limit')
                ->whereColumn(
                    'used_count',
                    '>=',
                    'usage_limit'
                ),

            default => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    public function sort(
        Builder $query,
        string $column = 'created_at',
        string $direction = 'desc'
    ): Builder {
        $allowedColumns = [
            'code',
            'discount_value',
            'start_date',
            'expiry_date',
            'usage_limit',
            'used_count',
            'created_at',
        ];

        if (! in_array($column, $allowedColumns, true)) {
            $column = 'created_at';
        }

        $direction = strtolower($direction);

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        return $query->orderBy(
            $column,
            $direction
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function stats(): array
    {
        return [
            'total' => Voucher::query()->count(),

            'active' => Voucher::query()
                ->where('is_active', true)
                ->count(),

            'inactive' => Voucher::query()
                ->where('is_active', false)
                ->count(),

            'expired' => Voucher::query()
                ->where('expiry_date', '<', now())
                ->count(),

            'limit_reached' => Voucher::query()
                ->whereNotNull('usage_limit')
                ->whereColumn(
                    'used_count',
                    '>=',
                    'usage_limit'
                )
                ->count(),
        ];
    }
}