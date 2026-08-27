<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // 1. جلب أول مستودع موجود في قاعدة البيانات
        $firstWarehouse = Warehouse::first();

        // 2. التحقق مما إذا كان هناك مستودع موجود
        if (!$firstWarehouse) {
            $this->command->error('لا يوجد أي مستودع في قاعدة البيانات. يرجى تشغيل WarehouseSeeder أولاً.');
            return; // توقف عن تنفيذ السيدر إذا لم يتم العثور على مستودع
        }

        // 3. إنشاء موقع واحد مرتبط بهذا المستودع الأول
        Location::firstOrCreate(
            [
                'name' => 'A1/علبة', // اسم فريد للموقع
                'warehouse_id' => $firstWarehouse->id
            ],
            [
                'description' => 'موقع عام في المستودع الرئيسي للاختبار',
                'capacity_units' => 5000,
                'capacity_unit_type' => 'علبة', // يمكنك تغيير نوع الوحدة حسب احتياجك
                'used_capacity_units' => 0,
            ]
        );

       // $this->command->info('تم إنشاء موقع واحد وربطه بأول مستودع بنجاح.');
    }
}
