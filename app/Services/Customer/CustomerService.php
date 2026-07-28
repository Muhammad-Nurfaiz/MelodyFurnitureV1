<?php

namespace App\Services\Customer;

use App\Models\Customer;

class CustomerService
{
    public function updateProfile(Customer $customer,array $data): Customer {
        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address_detail' => $data['address'],
        ]);
        return $customer->fresh();
    }
}