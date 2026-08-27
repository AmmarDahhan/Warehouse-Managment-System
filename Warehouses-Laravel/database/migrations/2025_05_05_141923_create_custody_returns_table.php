<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()//
    {
        Schema::create('custody_returns', function (Blueprint $table) {

            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // صاحب العهدة (المستخدم العادي)
            $table->date('return_date');
            $table->string('serial_number')->unique();
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled
//            $table->foreignId('processed_by_warehouse_keeper_id')->nullable()->constrained('warehouse_keepers')->onDelete('set null'); // أمين المستودع
//            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custody_returns');
    }
};
