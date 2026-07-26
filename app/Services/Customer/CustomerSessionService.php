<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerSessionService
{
    /*
    |--------------------------------------------------------------------------
    | Create Guest Session
    |--------------------------------------------------------------------------
    */

    public function create(array $data): Customer
    {
        $customer = Customer::create([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'guest_token' => Str::uuid(),
        ]);

        Cart::create([
            'customer_id' => $customer->id,
        ]);

        return $customer->fresh('cart');
    }

    public function resolve(
        ?string $guestToken,
        array $data = []
    ): Customer
    {
        if ($guestToken) {

            $customer = $this->findByToken($guestToken);

            if ($customer) {

                return $customer;

            }

        }

        return $this->create($data);
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Guest Token
    |--------------------------------------------------------------------------
    */

    public function findByToken(string $guestToken): ?Customer
    {
        return Customer::with('cart')
            ->where('guest_token', $guestToken)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Update Identity
    |--------------------------------------------------------------------------
    */

    public function update(Customer $customer,array $data): Customer
    {
        $customer->update([

            'name'=>$data['name'] ?? $customer->name,

            'phone'=>$data['phone'] ?? $customer->phone,

            'email'=>$data['email'] ?? $customer->email,

        ]);

        return $customer->fresh();
    }
}