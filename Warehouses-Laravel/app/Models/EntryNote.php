<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryNote extends Model
{
    protected $fillable = [
        'warehouse_id',
        'serial_number',
        'date',
        'created_by'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(WarehouseKeeper::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(EntryNoteItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
