<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('scrapped_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scrap_note_id')->constrained('scrap_notes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scrapped_materials');
    }
};
