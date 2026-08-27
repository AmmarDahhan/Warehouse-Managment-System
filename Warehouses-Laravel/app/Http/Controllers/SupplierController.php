<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $suppliers = Supplier::all();
        $count = $suppliers->count();

        $data = [
            'suppliers' => $suppliers,
            'count' => $count,
        ];

        return $this->successResponse(
            $data,
            'تم جلب قائمة الموردين بنجاح.'
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_info' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $supplier = Supplier::create($request->all());

            return $this->successResponse(
                $supplier,
                'تم إنشاء المورد بنجاح.',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل في إنشاء المورد.',
                500,
                [],
                'SUPPLIER_CREATION_FAILED'
            );
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->notFoundResponse('المورد غير موجود.');
        }

        return $this->successResponse(
            $supplier,
            'تم جلب بيانات المورد بنجاح.'
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->notFoundResponse('المورد غير موجود.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'contact_info' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $supplier->update($request->all());

            return $this->successResponse(
                $supplier,
                'تم تعديل بيانات المورد بنجاح.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل في تعديل المورد.',
                500,
                [],
                'SUPPLIER_UPDATE_FAILED'
            );
        }
    }
}
