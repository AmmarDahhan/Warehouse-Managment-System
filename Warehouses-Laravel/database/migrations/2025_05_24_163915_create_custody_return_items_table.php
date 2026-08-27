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
        Schema::create('custody_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custody_return_id')->constrained('custody_returns')->onDelete('cascade');
            $table->foreignId('custody_item_id')->constrained('custody_items')->onDelete('cascade'); // العنصر الأصلي من العهدة
            $table->decimal('returned_quantity', 10, 2); // الكمية المرتجعة
            $table->decimal('returned_quantity_accepted', 10, 2)->default(0);
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict'); // المستودع الذي سيتم الإرجاع إليه
//             إضافة location_id كـ Foreign Key، ويمكن أن يكون nullable في البداية
//             لأنه لا يتم تعيينه إلا عند معالجة أمين المستودع.
//            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null')->after('warehouse_id');
            $table->text('user_notes')->nullable(); // ملاحظات المستخدم عن حالة الغرض
            $table->string('warehouse_manager_status')->default('pending_review'); // pending_review, accepted, rejected, damaged, missing
            $table->text('warehouse_manager_notes')->nullable(); // ملاحظات أمين المستودع
            $table->timestamps();

            // إضافة قيد فريد لضمان عدم وجود نفس عنصر العهدة مرتين في نفس طلب الإرجاع
            $table->unique(['custody_return_id', 'custody_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custody_return_items');
    }
};
