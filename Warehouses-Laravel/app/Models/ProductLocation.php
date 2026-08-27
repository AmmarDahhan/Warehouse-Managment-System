<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
        'internal_shelf_number',
    ];

    // علاقة: سجل product_location ينتمي إلى منتج واحد
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // علاقة: سجل product_location ينتمي إلى موقع واحد
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

}
