<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('manager_id')->nullable()->constrained('managers');
            $table->foreignId('warehouse_keeper_id')->constrained('warehouse_keepers');
            $table->enum('status', ['pending','approved', 'rejected','delivered'])->default('pending');
            $table->string('serial_number')->unique();
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('material_requests');
    }
};
