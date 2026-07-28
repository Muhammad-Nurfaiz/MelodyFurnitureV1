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

                // Specification
                'dimensions' => '55 × 60 × 82 cm',
                'weight' => 8.50,
                'packing_weight' => 10.20,
                'load_capacity' => '120 kg',
                'material' => 'Solid Oak Wood + Fabric',
                'assembly_required' => true,
            ],

            [
                'name' => 'Minimalist Desk',
                'slug' => 'minimalist-desk',
                'price' => 1750000,
                'stock' => 15,

                // Specification
                'dimensions' => '120 × 60 × 75 cm',
                'weight' => 22.50,
                'packing_weight' => 25.80,
                'load_capacity' => '80 kg',
                'material' => 'Solid Wood + Steel Frame',
                'assembly_required' => true,
            ],

            [
                'name' => 'Wooden Cabinet',
                'slug' => 'wooden-cabinet',
                'price' => 2650000,
                'stock' => 8,

                // Specification
                'dimensions' => '90 × 45 × 180 cm',
                'weight' => 52.40,
                'packing_weight' => 58.70,
                'load_capacity' => '250 kg',
                'material' => 'Solid Mahogany Wood',
                'assembly_required' => false,
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
                    'total_sold' => rand(10, 100),
                ]
            );

            ProductSpecification::updateOrCreate(

                [
                    'product_id' => $product->id,
                ],

                [
                    'dimensions' => $item['dimensions'],
                    'weight' => $item['weight'],
                    'packing_weight' => $item['packing_weight'],
                    'load_capacity' => $item['load_capacity'],
                    'material_details' => $item['material'],
                    'assembly_required' => $item['assembly_required'],
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