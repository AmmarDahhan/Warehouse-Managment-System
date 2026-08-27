<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('warehouse_keepers');
            $table->foreignId('manager_id')->nullable()->constrained('managers');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('serial_number')->unique();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('request_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_requests');
    }
};
