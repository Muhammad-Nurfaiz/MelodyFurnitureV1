<?php

namespace Database\Seeders;

use App\Models\ShippingCourier;
use Illuminate\Database\Seeder;

class ShippingCourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $couriers = [
            [
                'code' => 'jnt_cargo',
                'name' => 'J&T Cargo',
                'is_active' => true,
            ],
            [
                'code' => 'sentral_cargo',
                'name' => 'Sentral Cargo',
                'is_active' => true,
            ],
        ];

        foreach ($couriers as $courier) {
            ShippingCourier::updateOrCreate(
                [
                    'code' => $courier['code'],
                ],
                $courier
            );
        }
    }
}