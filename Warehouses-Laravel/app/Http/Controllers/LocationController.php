<?php

namespace App\Http\Controllers;

use App\Models\EntryNoteItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ReceivingNoteItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class LocationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $locations = Location::with('warehouse')->get();
        if($locations->isEmpty()){
            return $this->successResponse(null,'لا يوجد مواقع');
        }

        return $this->successResponse($locations,'تم استرجاع المواقع بنجاح');
    }

    public function store(Request $request)
    {
        // التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255|unique:locations,name', // تأكد من فرادة الاسم
            'description' => 'nullable|string|max:500',
            'capacity_units' => 'required|numeric|min:0.01',
            'capacity_unit_type' => 'required|string', // أمثلة: 'pcs', 'kg', 'liter', 'volume'. أضف ما يناسبك. ///|in:pcs,kg,liter,volume//
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $location = Location::create([
                'warehouse_id' => $request->warehouse_id,
                'name' => $request->name,
                'description' => $request->description,
                'capacity_units' => $request->capacity_units,
                'capacity_unit_type' => $request->capacity_unit_type,
                'used_capacity_units' => 0, // دائماً تبدأ السعة المستخدمة بـ 0
            ]);

            return $this->successResponse( $location,'تم إنشاء الموقع بنجاح.');

        } catch (\Exception $e) {
            // يمكن إضافة تسجيل للخطأ هنا
            return $this->errorResponse('فشل في إنشاء الموقع: ' . $e->getMessage(), 500);
        }
    }



    public function searchAvailableLocations(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'warehouse_id'=>'required|exists:warehouses,id',
            'product_id'=>'required|exists:products,id',
            'quantity'=>'required|numeric|min:0.01',
            'preferred_location_id' => 'nullable|exists:locations,id', // موقع مفضل للبحث عنه أولاً
        ]);
        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }

        $warehouseId = $request->input('warehouse_id');
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $preferredLocationId=$request->input('preferred_location_id');

        $product= Product::find($productId);
        if (!$product) {
            return $this->notFoundResponse('المنتج غير موجود.');
        }

        // هون يتم التحقق: سعة الموقع المتبقية لازم تكون أكبر من أو تساوي الكمية المطلوبة،
        // ونوع وحدة السعة للموقع يجب أن يطابق وحدة المنتج.
        $query= Location::where('warehouse_id',$warehouseId)
            ->whereRaw('capacity_units - used_capacity_units >= ?',[$quantity])
            ->where('capacity_unit_type',$product->unit);

        // إذا كان هناك موقع مفضل، قم بالبحث عنه أولاً
        if($preferredLocationId){
            $preferredLocation = $query->clone()->where('id',$preferredLocationId)->first();
            if($preferredLocation){
                return $this->successResponse([$preferredLocation],'تم العثور على الموقع المفضل بسعة كافية');
            }
        }

        // إذا لم يتم العثور على الموقع المفضل أو لا تتوفر فيه سعة، تابع البحث عن أي موقع آخر
        $availableLocations = $query->get();
        if ($availableLocations->isEmpty()){
            return $this->successResponse([],'لا توجد مواقع تخزين متاحة بسعة كافية للمنتج المحدد في هذا المستودع');
        }

        return $this->successResponse($availableLocations,'تم استرجاع المواقع المتاحة بنجاح');
    }



    public function getProductLocations(Request $request)
    {

        // التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'nullable|exists:warehouses,id', // يمكن أن يكون اختيارياً للبحث في كل المستودعات
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        // استرجاع سجلات ProductLocation
        $query = ProductLocation::with('location.warehouse', 'product')
            ->where('product_id', $productId)
            ->where('quantity', '>', 0); // المواقع التي تحتوي على كمية فعلية

        if ($warehouseId) {
            $query->whereHas('location', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        $productLocations = $query->get();

        if ($productLocations->isEmpty()) {
            return $this->successResponse([],'لا توجد كميات لهذا المنتج في أي موقع بهذا المستودع.');
        }

        // تنسيق البيانات لتكون أكثر وضوحاً
        $formattedData = $productLocations->map(function ($pl) {
            return [
                'location_id' => $pl->location->id,
                'location_name' => $pl->location->name,
                'warehouse_id' => $pl->location->warehouse->id,
                'warehouse_name' => $pl->location->warehouse->name,
                'quantity_in_location' => $pl->quantity,
                'capacity_unit_type' => $pl->location->capacity_unit_type,
                'internal_shelf_number' => $pl->internal_shelf_number,
                'product_unit' => $pl->product->unit,
                'location_used_capacity' => $pl->location->used_capacity_units,
                'location_total_capacity' => $pl->location->capacity_units,
            ];
        });

        return $this->successResponse( $formattedData,'تم استرجاع مواقع المنتج بنجاح.');
    }

    public function assignLocation(Request $request,)
    {
        $request->validate([
            'type' => 'required|in:entry,receiving',
            'item_id' => 'required|integer',
            'location_id' => 'required|exists:locations,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $type = $request->type;
        $itemId = $request->item_id;

        // 1. تحديد المصدر (Entry أو Receiving)
        if ($type === 'entry') {
            $item = EntryNoteItem::findOrFail($itemId);
        } elseif ($type === 'receiving') {
            $item = ReceivingNoteItem::findOrFail($itemId);
        } else {
            return $this->errorResponse('نوع المذكرة غير صالح', 400);
        }

        // 2. التحقق من الكمية المتاحة
        if ($item->unassigned_quantity < $request->quantity) {
            return $this->errorResponse(
                'الكمية غير كافية للتوزيع',
                422,
                ['available' => $item->unassigned_quantity]
            );
        }

        // 3. التحقق من السعة المتاحة في الموقع
        $location = Location::findOrFail($request->location_id);
        $availableCapacity = $location->capacity_units - $location->used_capacity_units;

        if ($availableCapacity < $request->quantity) {
            return $this->errorResponse(
                'لا توجد سعة كافية في هذا الموقع',
                422,
                ['available_capacity' => $availableCapacity]
            );
        }

        // 4. عملية التوزيع
        DB::transaction(function () use ($item, $request, $location) {
            // البحث إذا كان هناك سجل لنفس المنتج والموقع
            $productLocation = ProductLocation::where('product_id', $item->product_id)
                ->where('location_id', $request->location_id)
                ->first();

            if ($productLocation) {
                // إذا موجود -> زيادة الكمية
                $productLocation->increment('quantity', $request->quantity);
            } else {
                // إذا غير موجود -> إنشاء سجل جديد
                ProductLocation::create([
                    'product_id' => $item->product_id,
                    'location_id' => $request->location_id,
                    'quantity' => $request->quantity,
                ]);
            }

            // خصم من الكمية الغير موزعة
            $item->decrement('unassigned_quantity', $request->quantity);

            // تحديث السعة المستخدمة
            $location->increment('used_capacity_units', $request->quantity);
        });

        return $this->successMessage('تم توزيع الكمية على الموقع بنجاح');
    }

    public function unassignedItems()
    {
        $entryItems = EntryNoteItem::where('unassigned_quantity', '>', 0)->get();
        $receivingItems = ReceivingNoteItem::where('unassigned_quantity', '>', 0)->get();

        $all = $entryItems->merge($receivingItems);

        return $this->successResponse($all, 'جميع المواد الغير موزعة');
    }


    public function show($id)
    {
        try {
            $location = Location::with('warehouse')->findOrFail($id);

            return $this->successResponse($location, 'تم استرجاع الموقع بنجاح');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('الموقع غير موجود');
        } catch (\Exception $e) {
            return $this->errorResponse('حدث خطأ أثناء استرجاع الموقع: ' . $e->getMessage(), 500);
        }
    }

}
