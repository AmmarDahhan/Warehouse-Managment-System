<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //
    use ApiResponse;
    /**
     * عرض جميع المستخدمين
     */
    public function index()
    {
        try {
            $users = User::get();
            return $this->successResponse($users, 'تم جلب المستخدمين بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * إنشاء مستخدم جديد
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'sometimes|email|unique:users',
                'phone' => 'sometimes|string|unique:users|regex:/^09\d{8}$/',
                'password' => 'required|string|min:8',
                'job_title' => 'required|string',
                'department_id' => 'required|exists:departments,id',
                'gender'       => 'sometimes|nullable|string',
                'address'      => 'sometimes|nullable|string',
                'birth_date'   => 'sometimes|nullable|date',
                'facebook_url'  => 'sometimes|nullable|string',
                'instagram_url' => 'sometimes|nullable|string',

            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $user = User::create($request->all());

            return $this->successResponse($user, 'تم إنشاء المستخدم بنجاح', 201);
        } catch (\Exception $e) {

            return $this->handleExceptionResponse($e);

        }
    }

    /**
     * عرض مستخدم معين
     */
    public function show($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->notFoundResponse('المستخدم غير موجود ');
            }

            return $this->successResponse($user, 'تم جلب المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * عرض مستخدم النشط حاليا (للموبايل)
     */
    public function showActive()
    {
        try {
            $user = Auth::user(); // ✅ المستخدم بناءً على التوكن الحالي

            if (!$user) {
                return $this->notFoundResponse('المستخدم غير موجود أو التوكن غير صالح');
            }

            return $this->successResponse($user, 'تم جلب المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


    /**
     * تحديث مستخدم
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->notFoundResponse('المستخدم غير موجود');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,'.$id,
                'phone' => 'sometimes|string|regex:/^09\d{8}$/|unique:users,phone,'.$id,
                'password' => 'sometimes|string|min:8',
                'job_title' => 'sometimes|string',
                'department_id' => 'sometimes|exists:departments,id',
                'gender'       => 'sometimes|nullable|string',
                'address'      => 'sometimes|nullable|string',
                'birth_date'   => 'sometimes|nullable|date',
                'facebook_url'  => 'sometimes|nullable|string',
                'instagram_url' => 'sometimes|nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $user->update($request->all());

            return $this->successResponse($user, 'تم تحديث المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * حذف مستخدم
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->notFoundResponse('المستخدم غير موجود');
            }

            $user->delete();

            return $this->successMessage('تم حذف المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


}
