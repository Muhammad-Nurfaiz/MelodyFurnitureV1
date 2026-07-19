<?php

namespace Database\Seeders;

use App\Models\Series;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $series = [

            [
                'name' => 'Scandinavian',
                'slug' => 'scandinavian'
            ],

            [
                'name' => 'Minimalist',
                'slug' => 'minimalist'
            ],

            [
                'name' => 'Industrial',
                'slug' => 'industrial'
            ],

            [
                'name' => 'Modern',
                'slug' => 'modern'
            ],

            [
                'name' => 'Luxury',
                'slug' => 'luxury'
            ]

        ];

        foreach ($series as $item) {
            Series::create($item);
        }
    }
}
