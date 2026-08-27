<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('departments')->insert([
            ['name' => 'IT',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'HR',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'IT2',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'HR2',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse2',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance2',     'created_at' => now(), 'updated_at' => now()],
            ['name' => 'HR3',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehouse3',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
