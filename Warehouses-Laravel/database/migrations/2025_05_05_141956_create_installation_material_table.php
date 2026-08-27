<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('installation_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_report_id')->constrained('installation_reports')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products');
//            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->string('product_name'); // نسخة من اسم المنتج وقت التركيب
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('installation_materials');
    }
};
