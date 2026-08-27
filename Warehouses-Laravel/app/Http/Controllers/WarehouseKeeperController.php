<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WarehouseKeeper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WarehouseKeeperController extends Controller
{
    //
    use ApiResponse;

    /**
     * عرض أمين المستودع معين
     */
    public function show()
    {
        try {
            // إذا عندك حارس (guard) منفصل لأمين المستودع
            // $keeper = Auth::guard('warehouse_keeper')->user();

            // إذا تستخدم نفس الحارس الأساسي
            $keeper = Auth::user();

            if (!$keeper || !($keeper instanceof WarehouseKeeper)) {
                return $this->notFoundResponse('أمين المستودع غير موجود أو التوكن غير صالح');
            }

            return $this->successResponse($keeper, 'تم جلب بيانات أمين المستودع بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


    /**
     * تحديث أمين المستودع
     */
    public function update(Request $request, $id)
    {
        try {
            $keeper = WarehouseKeeper::find($id);

            if (!$keeper) {
                return $this->notFoundResponse('أمين المستودع غير موجود');
            }

            $validator = Validator::make($request->all(), [
                'name'         => 'sometimes|string|max:255',
                'email'        => 'sometimes|nullable|email|unique:warehouse_keepers,email,' . $id,
                'phone'        => 'sometimes|nullable|string|unique:warehouse_keepers,phone,' . $id,
                'gender'       => 'sometimes|nullable|string',
                'address'      => 'sometimes|nullable|string',
                'birth_date'   => 'sometimes|nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validatedData = $validator->validated();

            $keeper->update($validatedData);

            return $this->successResponse($keeper, 'تم تحديث بيانات أمين المستودع بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * تحديث كلمة مرور أمين المستودع (بناءً على التوكن)
     */
    public function updatePassword(Request $request ,$id)
    {
        try {
            $keeper = WarehouseKeeper::find($id);


            if (!$keeper) {
                return $this->notFoundResponse('أمين المستودع غير موجود أو التوكن غير صالح');
            }

            // التحقق من المدخلات
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                // لازم تمرر new_password_confirmation
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            // التحقق من كلمة المرور الحالية
            if (!Hash::check($request->current_password, $keeper->password)) {
                return $this->errorResponse(
                    'كلمة المرور الحالية غير صحيحة',
                    422,
                    (array)'INVALID_CURRENT_PASSWORD'
                );
            }

            // تحديث كلمة المرور
            $keeper->password = Hash::make($request->new_password);
            $keeper->save();

            return $this->successResponse(null, 'تم تحديث كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }
}
