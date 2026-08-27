<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('entry_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_note_id')->constrained('entry_notes');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->float('unassigned_quantity', 10, 2)->default(0);
            $table->float('quantity', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entry_note_items');
    }
};
