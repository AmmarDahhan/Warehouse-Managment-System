<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $numberOfRooms = 30;

        for ($i = 0; $i < $numberOfRooms; $i++) {
            Room::create([
                'building_id' => 1,
                'room_code' => 'RR ' . ($i + 1),
            ]);

        }
    }
}
