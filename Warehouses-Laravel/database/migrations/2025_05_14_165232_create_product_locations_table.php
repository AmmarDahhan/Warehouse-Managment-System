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
        Schema::create('product_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->float('quantity', 10, 2); // الكمية من هذا المنتج في هذا الموقع المحدد
            // مثال: رف داخلي صغير أو درج ضمن الرف الكبير
            $table->string('internal_shelf_number')->nullable();
            $table->timestamps();

            // ضمان أن المنتج الواحد لا يتكرر في نفس الموقع (كل سجل يمثل كمية واحدة لمنتج واحد في موقع واحد)
            $table->unique(['product_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_locations');
    }
};
