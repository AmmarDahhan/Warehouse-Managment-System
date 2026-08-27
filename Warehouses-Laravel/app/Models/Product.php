<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'code',
        'unit',
        'consumable',
        'danger_quantity',
        'notes',
        'image_path',
    ];

    protected $casts = [
        'consumable' => 'boolean'
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function materialRequestItems()
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function entryNoteItems()
    {
        return $this->hasMany(EntryNoteItem::class);
    }

    public function receivingNoteItems()
    {
        return $this->hasMany(ReceivingNoteItem::class);
    }

    public function exitNoteItems()
    {
        return $this->hasMany(ExitNoteItem::class);
    }

    public function purchaseRequestItems()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function custodyItems()
    {
        return $this->hasMany(CustodyItem::class);
    }

    public function installationMaterials()
    {
        return $this->hasMany(InstallationMaterial::class);
    }

    public function scrappedMaterials()
    {
        return $this->hasMany(ScrappedMaterial::class);
    }

    public function productMovements()
    {
        return $this->hasMany(ProductMovement::class);
    }

    // الاسم المختصر للعلاقة مع حركات المنتج
    public function movements()
    {
        return $this->hasMany(ProductMovement::class);
    }

    // علاقة: المنتج يمكن أن يتواجد في العديد من المواقع (عبر جدول product_locations)
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'product_locations')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // علاقة: المنتج لديه العديد من سجلات product_locations
    public function productLocations()
    {
        return $this->hasMany(ProductLocation::class);
    }


    // دالة للوصول إلى الصورة
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }

        // صورة افتراضية إذا لم توجد صورة
        return asset('images/default.jpg');
    }
}
