<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrappedMaterial extends Model
{
    protected $fillable = [
        'scrap_note_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'location_id',
        'notes'
    ];

    public function scrapNote()
    {
        return $this->belongsTo(ScrapNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
