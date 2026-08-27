<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMovement extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'reference_serial',
        'prv_quantity',
        'note_quantity',
        'after_quantity',
        'date',
        'image_path',
        'notes'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // أنواع الحركات المسموحة
    public static function getValidTypes()
    {
        return [
            'entry' => 'إدخال',
            'exit' => 'إخراج',
            'receive' => 'استلام',
            'install' => 'تركيب',
            'scrap' => 'تلف'
        ];
    }
}
