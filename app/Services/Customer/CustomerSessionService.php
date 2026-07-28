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

        Cart::firstOrCreate([
            'customer_id' => $customer->id,
        ]);

        return $customer->fresh('cart');
    }

    public function resolve(
        ?string $guestToken,
        array $data = []
    ): Customer
    {
        /*
        |--------------------------------------------------------------------------
        | Guest Token
        |--------------------------------------------------------------------------
        */

        if ($guestToken) {

            $customer = $this->findByToken($guestToken);

            if ($customer) {

                return $this->update($customer, $data);

            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Customer
        |--------------------------------------------------------------------------
        */

        $customer = $this->findExistingCustomer($data);

        if ($customer) {

            $customer->update([
                'guest_token' => (string) Str::uuid(),
            ]);

            return $this->update($customer, $data);

        }

        /*
        |--------------------------------------------------------------------------
        | Create New Customer
        |--------------------------------------------------------------------------
        */

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

    public function update(Customer $customer, array $data): Customer
    {
        $customer->fill([
            'name' => $data['name'] ?? $customer->name,
            'phone' => $data['phone'] ?? $customer->phone,
            'email' => $data['email'] ?? $customer->email,
        ]);
        if ($customer->isDirty()) {
            $customer->save();
        }
        return $customer->fresh('cart');
    }

    private function findExistingCustomer(array $data): ?Customer
    {
        return Customer::query()

            ->when(
                !empty($data['phone']),
                fn ($query) => $query->orWhere('phone', $data['phone'])
            )

            ->when(
                !empty($data['email']),
                fn ($query) => $query->orWhere('email', $data['email'])
            )

            ->first();
    }
}