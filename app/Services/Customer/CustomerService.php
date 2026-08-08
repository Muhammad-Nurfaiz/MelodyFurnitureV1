<?php

namespace App\Services\Customer;

use App\Models\Customer;

class CustomerService
{
    /*
    |--------------------------------------------------------------------------
    | Resolve Checkout Customer
    |--------------------------------------------------------------------------
    |
    | Customer checkout ditentukan berdasarkan nomor telepon.
    |
    | - Phone sudah ada:
    |      update data customer dengan data checkout terbaru.
    |
    | - Phone belum ada:
    |      buat customer baru.
    |
    | Customer dari guest token tidak digunakan di sini.
    |
    */

    public function resolveCheckoutCustomer(array $data): Customer
    {
        $customer = Customer::query()
            ->where('phone', $data['phone'])
            ->first();

        if ($customer) {

            $customer->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'address_detail' => $data['address'],
            ]);

            return $customer->fresh();
        }

        return Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address_detail' => $data['address'],
        ]);
    }
}