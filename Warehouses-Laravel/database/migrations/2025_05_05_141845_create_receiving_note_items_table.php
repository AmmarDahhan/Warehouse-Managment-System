<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('receiving_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_note_id')->constrained('receiving_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->float('unit_price', 12, 2);
            $table->float('quantity', 10, 2);
            $table->float('total_price', 12, 2);
            $table->float('unassigned_quantity', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receiving_note_items');
    }
};
