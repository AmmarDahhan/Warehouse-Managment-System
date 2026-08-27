<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseKeeper;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class WarehouseController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        try {
            // ⚠️ 1. الحصول على أمين المستودع الموثق مباشرة من التوكن
            $authenticatedWarehouseKeeper = Auth::user();

            $warehouse = DB::transaction(function () use ($validated, $authenticatedWarehouseKeeper) {
                // إنشاء المستودع
                $newWarehouse = Warehouse::create([
                    'name' => $validated['name'],
                    'location' => $validated['location'],
                    'warehouse_keeper_id' => $authenticatedWarehouseKeeper->id, // ✅ ربط المستودع مع الأمين
                ]);

                return $newWarehouse;
            });

            return $this->successResponse(
                ['warehouse' => $warehouse],
                'تم إنشاء المستودع بنجاح وربطه بأمين المستودع.',
                201
            );
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e, 'فشل في إنشاء المستودع');
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'string|max:255',
                'location' => 'string|max:255',
            ]);

            $warehouse = Warehouse::find($id);

            if (!$warehouse) {
                return $this->notFoundResponse('المستودع غير موجودة');
            }

            $warehouse->update($validated);

            DB::commit();
            return $this->successResponse($warehouse, 'تم تعديل المستودع بنجاح', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->handleExceptionResponse($e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $warehouse = Warehouse::findOrFail($id);

            //Stock::where('warehouse_id', $warehouse->id)->delete();

            $warehouse->delete();

            DB::commit();

            return $this->successResponse(null, 'تم حذف المستودع والمخزون المرتبط به بنجاح');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('المستودع غير موجود', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('فشل في حذف المستودع: ' . $e->getMessage(), 500);
        }
    }

    public function index()
    {
        $warehouseKeeper = Auth::user(); // أمين المستودع المسجل الدخول

        $warehouses = $warehouseKeeper->warehouses()->get();

        if ($warehouses->isEmpty()) {
            return $this->errorResponse(
                'عذراً، لم تقم بإنشاء أي مستودع بعد.',
                403,
                [],
                'NO_WAREHOUSES'
            );
        }

        return $this->successResponse(
            $warehouses,
            'هذه هي كل المستودعات يا عمي.',
            200
        );
    }


    public function show($id)
    {
        try {
            $warehouse = Warehouse::with(['warehouseKeeper', 'stock.product'])->find($id);
            // استخدم with() لجلب علاقة المنتجات 'products'

            if (!$warehouse) {
                return $this->notFoundResponse('المستودع غير موجود');
            }

            return $this->successResponse(
                ['warehouse' => $warehouse],
                'تم جلب بيانات المستودع مع المنتجات بنجاح',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse('فشل في جلب بيانات المستودع: ' . $e->getMessage(), 500);
        }
    }
}
