<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Manager;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    use ApiResponse;

    /**
     * عرض المدير المعين
     */
    public function show()
    {
        try {
            // إذا عندك حارس (guard) منفصل للمدير
            // $manager = Auth::guard('manager')->user();

            // إذا تستخدم نفس الحارس الأساسي
            $manager = Auth::user();

            if (!$manager || !($manager instanceof Manager)) {
                return $this->notFoundResponse('المدير غير موجود أو التوكن غير صالح');
            }

            return $this->successResponse($manager, 'تم جلب بيانات المدير بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * تحديث المدير
     */
    public function update(Request $request, $id)
    {
        try {
            $manager = Manager::find($id);

            if (!$manager) {
                return $this->notFoundResponse('المدير غير موجود');
            }

            $validator = Validator::make($request->all(), [
                'name'         => 'sometimes|string|max:255',
                'email'        => 'sometimes|nullable|email|unique:managers,email,' . $id,
                'phone'        => 'sometimes|nullable|string|unique:managers,phone,' . $id,
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validatedData = $validator->validated();

            $manager->update($validatedData);

            return $this->successResponse($manager, 'تم تحديث بيانات المدير بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * تحديث كلمة مرور المدير (بناءً على التوكن)
     */
    public function updatePassword(Request $request, $id)
    {
        try {
            $manager = Manager::find($id);

            if (!$manager) {
                return $this->notFoundResponse('المدير غير موجود أو التوكن غير صالح');
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
            if (!Hash::check($request->current_password, $manager->password)) {
                return $this->errorResponse(
                    'كلمة المرور الحالية غير صحيحة',
                    422,
                    (array)'INVALID_CURRENT_PASSWORD'
                );
            }

            // تحديث كلمة المرور
            $manager->password = Hash::make($request->new_password);
            $manager->save();

            return $this->successResponse(null, 'تم تحديث كلمة المرور بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


}
