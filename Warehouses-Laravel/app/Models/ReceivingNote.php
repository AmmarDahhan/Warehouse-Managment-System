<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivingNote extends Model
{
    protected $fillable = [
        'purchase_requests_id',
        'supplier_id',
        'created_by',
        'serial_number',
        'date'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    // العلاقة لجلب البنود (واحد إلى متعدد)
    public function items()
    {
        return $this->hasMany(ReceivingNoteItem::class);
    }

// العلاقة لجلب المورد (متعدد إلى واحد)
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

// العلاقة لجلب طلب الشراء
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_requests_id');
    }

// العلاقة لجلب منشئ الإيصال
    public function createdBy()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'created_by');
    }
}
