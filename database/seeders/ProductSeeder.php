<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductSpecification;
use App\Models\Series;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::first();

        if (!$category) {
            return;
        }

        $series = Series::first();

        $products = [

            [
                'name' => 'Nordic Chair',
                'slug' => 'nordic-chair',
                'price' => 850000,
                'stock' => 20,
            ],

            [
                'name' => 'Minimalist Desk',
                'slug' => 'minimalist-desk',
                'price' => 1750000,
                'stock' => 15,
            ],

            [
                'name' => 'Wooden Cabinet',
                'slug' => 'wooden-cabinet',
                'price' => 2650000,
                'stock' => 8,
            ],

        ];

        foreach ($products as $item) {

            $product = Product::updateOrCreate(

                [
                    'slug' => $item['slug'],
                ],

                [

                    'category_id' => $category->id,

                    'series_id' => $series?->id,

                    'name' => $item['name'],

                    'description' => 'Produk demo untuk testing checkout.',

                    'product_detail' => '<p>Detail produk demo.</p>',

                    'original_price' => $item['price'],

                    'discount_price' => null,

                    'discount_percentage' => null,

                    'is_sale' => false,

                    'ready_stock' => $item['stock'],

                    'locked_stock' => 0,

                    'origin_city' => 'Malang',

                    'average_rating' => 4.8,

                    'total_sold' => rand(10,100),

                ]

            );

            ProductSpecification::updateOrCreate(

                [

                    'product_id' => $product->id,

                ],

                [

                    'dimensions' => '80 x 60 x 90 cm',

                    'seat_height' => '45 cm',

                    'load_capacity' => '120 kg',

                    'material_details' => 'Solid Wood',

                ]

            );

            ProductMedia::updateOrCreate(

                [

                    'product_id' => $product->id,

                    'sort_order' => 1,

                ],

                [

                    'media_type' => 'image',

                    'media_url' => 'products/demo/' . Str::slug($product->name) . '.jpg',

                    'thumbnail_url' => null,

                    'alt_text' => $product->name,

                    'is_main' => true,

                ]

            );

        }
    }
}