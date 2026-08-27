<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Location;
use App\Models\ProductLocation;

class InventoryService
{
    /**
     * خصم كمية من مخزون منتج من موقع محدد
     *
     * @throws \Exception
     */
    public function deductFromLocation(int $productId, int $locationId, float $quantity): void
    {
        $product = Product::findOrFail($productId);
        $location = Location::findOrFail($locationId);

        // تحقق من مطابقة وحدة القياس
        if ($product->unit !== $location->capacity_unit_type) {
            throw new \Exception("لا يمكن خصم المنتج (وحدته: {$product->unit}) من الموقع (وحدته: {$location->capacity_unit_type}). يجب أن تتطابق الوحدات.");
        }

        $productLocation = ProductLocation::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        if (!$productLocation || $productLocation->quantity < $quantity) {
            $available = $productLocation ? $productLocation->quantity : 0;
            throw new \Exception("الكمية المطلوبة ({$quantity}) للمنتج '{$product->name}' غير متوفرة في الموقع '{$location->name}' (المتاح: {$available}).");
        }

        // خصم من الكميات
        $productLocation->decrement('quantity', $quantity);
        $location->decrement('used_capacity_units', $quantity);
    }
}
