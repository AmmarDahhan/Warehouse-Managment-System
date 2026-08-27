<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustodyReturn extends Model
{

    protected $fillable = [
        'user_id',
        'return_date',
        'notes',
        'status',
        'serial_number',
        'warehouse_keeper_id',
        'processed_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class); // هدا هو المستخدم صاحب العهدة
    }

//    //  العلاقة تشير إلى WarehouseKeeper
//    public function warehouseKeeper()
//    {
//        return $this->belongsTo(WarehouseKeeper::class, 'warehouse_keeper_id');
//    }

    public function items()
    {
        return $this->hasMany(CustodyReturnItem::class);
    }
}
