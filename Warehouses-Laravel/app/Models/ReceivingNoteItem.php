<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivingNoteItem extends Model
{
    protected $fillable = [
        'receiving_note_id',
        'product_id',
        'warehouse_id',
        'unit_price',
        'quantity',
        'unassigned_quantity',
        'total_price',
        'notes'
    ];

    public function receivingNote()
    {
        return $this->belongsTo(ReceivingNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_price = $model->quantity * $model->unit_price;
        });
    }
}
