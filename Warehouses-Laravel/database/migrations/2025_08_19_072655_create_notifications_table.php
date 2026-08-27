<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notifiable_id');      // ID للشخص (موظف/مدير/أمين مستودع)
            $table->string('notifiable_type');               // نوع الجدول (user, Manager, warehouseKeeper)
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('type')->nullable();              // مثلاً: request_created, request_approved
            $table->unsignedBigInteger('related_id')->nullable(); // ID للطلب أو أي شي مرتبط
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['notifiable_id', 'notifiable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
