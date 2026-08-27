<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BuildingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $numberOfBuildings = 10;
        $location = 'دمشق';

        for ($i = 0; $i < $numberOfBuildings; $i++) {
            Building::create([
                'name' => 'مبنى ' . ($i + 1),
                'location' => $location,
            ]);

        }
    }
}
