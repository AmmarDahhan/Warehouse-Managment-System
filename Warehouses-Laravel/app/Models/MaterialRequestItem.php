<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $fillable = [
        'material_request_id',
        'product_id',
        'quantity_requested',
        'quantity_approved',
        'prev_quantity',
        'prev_date',
        'notes'
    ];

    protected $casts = [
        'prev_date' => 'date'
    ];

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 