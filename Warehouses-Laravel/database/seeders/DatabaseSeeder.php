<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        //$this->call(DepartmentSeeder::class);
//        $this->call(WarehouseSeeder::class);

        //$this->call(ProductsSeeder::class);

//        $this->call(BuildingsSeeder::class);
        //$this->call(RoomSeeder::class);
        $this->call(LocationSeeder::class);
    }
}
