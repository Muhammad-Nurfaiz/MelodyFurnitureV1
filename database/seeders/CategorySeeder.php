<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [

            [
                'name' => 'Chair',
                'slug' => 'chair'
            ],

            [
                'name' => 'Table',
                'slug' => 'table'
            ],

            [
                'name' => 'Sofa',
                'slug' => 'sofa'
            ],

            [
                'name' => 'Cabinet',
                'slug' => 'cabinet'
            ],

            [
                'name' => 'Storage',
                'slug' => 'storage'
            ]

        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
