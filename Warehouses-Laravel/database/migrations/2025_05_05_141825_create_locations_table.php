<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('name')->unique(); // مثال: "رف A1", "خزانة 3", "منطقة التخزين العامة"
            $table->text('description')->nullable();
            $table->float('capacity_units', 10, 2); // السعة الكلية لهذا الموقع (مثال: 100 كجم، 500 قطعة صغيرة)
            $table->string('capacity_unit_type'); // نوع وحدة السعة (مثال: 'pcs' للقطع، 'kg' للكيلوغرام، 'volume' للحجم)
            $table->float('used_capacity_units', 10, 2)->default(0); // السعة المستخدمة حالياً
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
