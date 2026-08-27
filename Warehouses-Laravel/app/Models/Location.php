<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'name',
        'description',
        'capacity_units',
        'capacity_unit_type',
        'used_capacity_units',
    ];

    // علاقة: الموقع ينتمي إلى مستودع واحد
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // علاقة: الموقع يمكن أن يحتوي على عدة منتجات (عبر جدول product_locations)
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_locations')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // علاقة: الموقع لديه العديد من سجلات product_locations
    public function productLocations()
    {
        return $this->hasMany(ProductLocation::class);
    }
}
