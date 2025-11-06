<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        $products = [];
        $now = now();

        $recordCount = 30;

        for ($i = 0; $i < $recordCount; $i++) {
            $products[] = [
                'name' => mb_substr($faker->unique()->realText(20), 0, 20),
                'description' => $faker->optional(0.7)->realText(120),
                'rating' => $faker->randomFloat(1, 0, 5),
                'download_count' => $faker->numberBetween(0, 10000),
                'image_url' => 'https://picsum.photos/800',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Product::insert($products);
    }
}
