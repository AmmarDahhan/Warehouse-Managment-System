<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('scrap_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('warehouse_keepers');
            $table->foreignId('approved_by')->nullable()->constrained('managers');
            $table->string('serial_number')->unique();
            $table->text('reason')->nullable();
            $table->date('date');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scrap_notes');
    }
};
