<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * عرض كل الإشعارات الخاصة بالمستخدم الحالي.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();  // الشخص الحالي (ممكن يكون user أو Manager أو warehouseKeeper)

            $notifications = $user->notifications()
                ->latest()
                ->get();

            return $this->successResponse($notifications, 'تم جلب الإشعارات بنجاح');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    /**
     * عرض إشعار محدد.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $notification = $user->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return $this->notFoundResponse('الإشعار غير موجود');
            }

            // تعليم الإشعار كمقروء بشكل مباشر عند عرضه
            $notification->update(['is_read' => true]);


            return $this->successResponse($notification, 'تم جلب الإشعار وتعليمه كمقروء');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e);
        }
    }


    /**
     * تعليم جميع الإشعارات كمقروءة.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();

            $user->notifications()
                ->update(['is_read' => true]);


            return $this->successMessage('تم تعليم جميع الإشعارات كمقروءة');
        } catch (\Throwable $e) {
            return $this->handleExceptionResponse($e);
        }
    }
}
