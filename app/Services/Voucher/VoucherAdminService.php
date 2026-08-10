<?php

namespace App\Services\Voucher;

use App\Models\Voucher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VoucherAdminService
{
    public function __construct(
        protected VoucherQueryService $queries,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Voucher List
    |--------------------------------------------------------------------------
    */

    public function list(Request $request): LengthAwarePaginator {
        return $this->paginate($request);
    }

    public function paginate(Request $request,int $perPage = 15): LengthAwarePaginator {
        $query = $this->queries->query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->search(
            $query,
            $request->input('search')
        );

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->filterStatus(
            $query,
            $request->input('status')
        );

        /*
        |--------------------------------------------------------------------------
        | Discount Type
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->filterDiscountType(
            $query,
            $request->input('discount_type')
        );

        /*
        |--------------------------------------------------------------------------
        | Expired
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->filterExpired(
            $query,
            $request->input('expired')
        );

        /*
        |--------------------------------------------------------------------------
        | Usage
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->filterUsage(
            $query,
            $request->input('usage')
        );

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $query = $this->queries->sort(
            $query,
            $request->input('sort', 'created_at'),
            $request->input('direction', 'desc')
        );

        return $this->queries->paginate($query,$perPage);
    }


    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Voucher {
        $validated = $this->validateCreate($data);

        /*
        |--------------------------------------------------------------------------
        | Normalize Data
        |--------------------------------------------------------------------------
        */

        $validated = $this->normalizeData($validated);

        /*
        |--------------------------------------------------------------------------
        | Default Values
        |--------------------------------------------------------------------------
        */

        $validated['used_count'] = 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        /*
        |--------------------------------------------------------------------------
        | Create Voucher
        |--------------------------------------------------------------------------
        */

        return Voucher::create($validated);
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(string $id,array $data): Voucher {
        $voucher = $this->show($id);
        $validated = $this->validateUpdate($data,$voucher);

        /*
        |--------------------------------------------------------------------------
        | Normalize Data
        |--------------------------------------------------------------------------
        */

        $validated = $this->normalizeData($validated);

        /*
        |--------------------------------------------------------------------------
        | Update Voucher
        |--------------------------------------------------------------------------
        */

        $voucher->update($validated);
        return $voucher->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    public function show(string $id): Voucher {
        $voucher = $this->queries->find($id);
        if (! $voucher) {
            abort(404);
        }
        return $voucher;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function delete(Voucher $voucher): void {
        $voucher->delete();
    }


    /*
    |--------------------------------------------------------------------------
    | Toggle Active
    |--------------------------------------------------------------------------
    */

    public function toggleActive(Voucher $voucher): Voucher {
        $voucher->update(['is_active' => ! $voucher->is_active]);
        return $voucher->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Find By Code
    |--------------------------------------------------------------------------
    */

    public function findByCode(string $code): ?Voucher {
        return $this->queries->findByCode($code);
    }


    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function stats(): array {
        return $this->queries->stats();
    }


    /*
    |--------------------------------------------------------------------------
    | Validation - Create
    |--------------------------------------------------------------------------
    */

    protected function validateCreate(array $data): array {

        $validator = Validator::make(
            $data,
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:vouchers,code',
                ],

                'discount_type' => [
                    'required',
                    Rule::in([
                        'percentage',
                        'fixed',
                    ]),
                ],

                'discount_value' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'min_order_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'max_discount_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'start_date' => [
                    'nullable',
                    'date',
                ],

                'expiry_date' => [
                    'required',
                    'date',
                ],

                'usage_limit' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'code.required' =>
                    'Kode voucher wajib diisi.',

                'code.max' =>
                    'Kode voucher maksimal 50 karakter.',

                'code.unique' =>
                    'Kode voucher sudah digunakan.',

                'discount_type.required' =>
                    'Jenis diskon wajib dipilih.',

                'discount_type.in' =>
                    'Jenis diskon tidak valid.',

                'discount_value.required' =>
                    'Nilai diskon wajib diisi.',

                'discount_value.numeric' =>
                    'Nilai diskon harus berupa angka.',

                'discount_value.min' =>
                    'Nilai diskon tidak boleh kurang dari 0.',

                'min_order_amount.numeric' =>
                    'Minimal pembelian harus berupa angka.',

                'min_order_amount.min' =>
                    'Minimal pembelian tidak boleh kurang dari 0.',

                'max_discount_amount.numeric' =>
                    'Maksimal diskon harus berupa angka.',

                'max_discount_amount.min' =>
                    'Maksimal diskon tidak boleh kurang dari 0.',

                'start_date.date' =>
                    'Tanggal mulai tidak valid.',

                'expiry_date.required' =>
                    'Tanggal expired wajib diisi.',

                'expiry_date.date' =>
                    'Tanggal expired tidak valid.',

                'usage_limit.integer' =>
                    'Batas penggunaan harus berupa angka bulat.',

                'usage_limit.min' =>
                    'Batas penggunaan minimal 1.',

                'is_active.boolean' =>
                    'Status aktif tidak valid.',
            ]
        );

        $validator->after(function ($validator) use ($data) {
            $this->validateBusinessRules($validator,$data);
        });
        return $validator->validate();
    }


    /*
    |--------------------------------------------------------------------------
    | Validation - Update
    |--------------------------------------------------------------------------
    */

    protected function validateUpdate(array $data,Voucher $voucher): array {

        $validator = Validator::make(
            $data,
            [
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('vouchers', 'code')
                        ->ignore($voucher->id),
                ],

                'discount_type' => [
                    'required',
                    Rule::in([
                        'percentage',
                        'fixed',
                    ]),
                ],

                'discount_value' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'min_order_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'max_discount_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'start_date' => [
                    'nullable',
                    'date',
                ],

                'expiry_date' => [
                    'required',
                    'date',
                ],

                'usage_limit' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ]
        );

        $validator->after(function ($validator) use ($data) {
                $this->validateBusinessRules($validator,$data);
            }
        );
        return $validator->validate();
    }


    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */

    protected function validateBusinessRules($validator,array $data): void {

        /*
        |--------------------------------------------------------------------------
        | Discount Value
        |--------------------------------------------------------------------------
        */

        $discountType = $data['discount_type'] ?? null;
        $discountValue = $data['discount_value'] ?? null;

        if ($discountType === 'percentage' && $discountValue !== null && (float) $discountValue > 100) {
            $validator->errors()->add(
                'discount_value',
                'Diskon persentase tidak boleh lebih dari 100%.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Start / Expiry Date
        |--------------------------------------------------------------------------
        */

        $startDate = $data['start_date'] ?? null;
        $expiryDate = $data['expiry_date'] ?? null;

        if ($startDate && $expiryDate) {
            try {
                $start = \Carbon\Carbon::parse($startDate);
                $expiry = \Carbon\Carbon::parse($expiryDate);

                if ($expiry->lessThanOrEqualTo($start)) {
                    $validator->errors()->add(
                        'expiry_date',
                        'Tanggal expired harus setelah tanggal mulai.'
                    );
                }
            } catch (\Throwable) {
                // Rule `date` menangani invalid date.
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Maximum Discount
        |--------------------------------------------------------------------------
        */

        $maxDiscount = $data['max_discount_amount'] ?? null;

        if ($discountType === 'fixed' && $maxDiscount !== null && (float) $maxDiscount > 0) {

            $validator->errors()->add(
                'max_discount_amount',
                'Maksimal diskon hanya digunakan untuk diskon persentase.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Data
    |--------------------------------------------------------------------------
    */

    protected function normalizeData(array $data): array {

        /*
        |--------------------------------------------------------------------------
        | Code
        |--------------------------------------------------------------------------
        */

        if (isset($data['code'])) {
            $data['code'] = strtoupper(
                trim($data['code'])
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Empty Numeric Fields
        |--------------------------------------------------------------------------
        */

        foreach ([
            'min_order_amount',
            'max_discount_amount',
            'usage_limit',
        ] as $field) {

            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Empty Start Date
        |--------------------------------------------------------------------------
        */

        if (array_key_exists('start_date', $data) && $data['start_date'] === '') {
            $data['start_date'] = null;
        }
        return $data;
    }
}