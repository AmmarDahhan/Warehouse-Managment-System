<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory ;


class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create();
        $numberOfProducts = 10;
        $nameOptions = ['قلم', 'حنفية', 'محارم', 'دفتر', 'مسطرة'];
        $unitOptions = ['قطعة', 'علبة', 'كرتونة', 'كيلوغرام', 'لتر'];

        for ($i = 0; $i < $numberOfProducts; $i++) {
            Product::create([
                'name' => $faker->randomElement($nameOptions),
                'code' => $faker->unique()->ean13,
                'unit' => $faker->randomElement($unitOptions),
                'consumable' => $faker->boolean(70),
                'notes' => $faker->optional()->sentence,
            ]);
        }
    }
}
