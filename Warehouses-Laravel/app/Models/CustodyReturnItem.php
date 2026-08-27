<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustodyReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'custody_return_id',
        'custody_item_id',
        'returned_quantity',
        'returned_quantity_accepted',
        'warehouse_id',
        'location_id',
        'user_notes',
        'warehouse_manager_status',
        'warehouse_manager_notes',
    ];

    // Relationships
    public function custodyReturn()
    {
        return $this->belongsTo(CustodyReturn::class);
    }

    public function custodyItem()
    {
        return $this->belongsTo(CustodyItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
