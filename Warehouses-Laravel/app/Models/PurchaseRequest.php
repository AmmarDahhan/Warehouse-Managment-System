<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'created_by',
        'manager_id',
        'supplier_id',
        'serial_number',
        'status',
        'request_date',
        'notes'
    ];

    protected $casts = [
        'request_date' => 'date'
    ];

    public function createdBy()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'created_by');
    }

    public function manager()
    {
        return $this->belongsTo(Manager::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }
} 