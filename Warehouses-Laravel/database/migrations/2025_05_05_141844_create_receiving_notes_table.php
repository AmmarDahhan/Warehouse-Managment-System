<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('receiving_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requests_id')->constrained('purchase_requests');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('created_by')->constrained('warehouse_keepers');
            $table->string('serial_number')->unique();
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receiving_notes');
    }
};
