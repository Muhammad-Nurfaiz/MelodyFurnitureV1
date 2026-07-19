<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'full_name' => 'Super Admin',
            'email' => 'admin@melodyfurniture.com',
            'password' => Hash::make('12345asd'),
            'phone_number' => '081234567890'
        ]);
    }
}