<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('custody_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custody_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('exit_note_id')->constrained('exit_notes');
            $table->foreignId('room_id')->nullable()->constrained('rooms');
            $table->decimal('quantity', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custody_items');
    }
};
