<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('manager_id')->nullable()->constrained('managers')->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->unique()->constrained('warehouses')->onDelete('set null'); // ⚠️ جعلناه فريداً لمنع ربط أكثر من قسم بنفس المستودع
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};
