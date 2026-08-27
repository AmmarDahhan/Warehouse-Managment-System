<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Manager;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    use ApiResponse;
    // CREATE - إضافة قسم جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|exists:managers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id', // ⚠️ جديد: أصبح اختيارياً
            'description' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $department = Department::create($validated);

            // ⚠️   تحديث المدير بعد إنشاء القسم
            if (isset($validated['manager_id'])) {
                $manager = Manager::find($validated['manager_id']);
                if ($manager) {
                    $manager->update(['department_id' => $department->id]);
                }
            }

//            if (isset($validated['warehouse_id'])) {
//                $warehouse = Warehouse::find($validated['warehouse_id']);
//                if ($warehouse) {
//                    $warehouse->update(['department_id' => $department->id]);
//                }
//            }

            return $this->successResponse($department, 'تم إنشاء القسم بنجاح', 201);
        }, 3);
    }

    // READ - جلب جميع الأقسام
    public function index()
    {
        try {
            $departments = Department::with(['manager', 'warehouse'])->get();
            return $this->successResponse($departments, 'تم جلب الأقسام بنجاح');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e, 'فشل في جلب الأقسام');
        }
    }

    // READ - جلب قسم واحد حسب ID
    public function show($id)
    {
        try {
            $department = Department::with(['manager', 'warehouse'])->find($id);

            if (!$department) {
                return $this->notFoundResponse('القسم غير موجود');
            }

            return $this->successResponse($department, 'تم جلب بيانات القسم بنجاح');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e, 'فشل في جلب بيانات القسم');
        }
    }

    // UPDATE - تحديث بيانات قسم
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $department = Department::find($id);

            if (!$department) {
                return $this->notFoundResponse('القسم غير موجود');
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'manager_id' => 'sometimes|exists:managers,id',
                'description' => 'nullable|string',
            ]);

            $department->update($validated);

            return $this->successResponse($department, 'تم تحديث بيانات القسم بنجاح');
        });
    }

    // DELETE - حذف قسم
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $department = Department::find($id);

            if (!$department) {
                return $this->notFoundResponse('القسم غير موجود');
            }

            $department->delete();

            return $this->successResponse(null,'تم حذف القسم بنجاح');
        });
    }

    public function indexManager()
    {
        try {
            $departments = Manager::get();
            return $this->successResponse($departments, 'تم جلب المدراء بنجاح');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e, 'فشل في جلب المدراء');
        }
    }
}
