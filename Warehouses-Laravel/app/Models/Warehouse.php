<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'location',
        'warehouse_keeper_id',
    ];

    public function department()
    {
        // العلاقة الصحيحة: مستودع له قسم واحد (hasOne)
        // القسم يحتوي على warehouse_id
        return $this->hasOne(Department::class, 'warehouse_id');
    }

    public function warehouseKeeper()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'warehouse_keeper_id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    public function entryNotes()
    {
        return $this->hasMany(EntryNote::class);
    }

    public function receivingNotes()
    {
        return $this->hasMany(ReceivingNote::class);
    }

    public function exitNotes()
    {
        return $this->hasMany(ExitNote::class);
    }

    // علاقة: المستودع يمكن أن يحتوي على العديد من المواقع
    public function locations()
    {
        return $this->hasMany(Location::class);
    }

}
