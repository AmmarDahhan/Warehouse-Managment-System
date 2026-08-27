<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Manager;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Traits\ApiResponse;
use App\Services\NotificationService;


class MaterialRequestController extends Controller
{
    use ApiResponse;
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse(
                    message: 'غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.',
                    code: 401,
                    internalCode: 'UNAUTHORIZED_ACCESS'
                );
            }

            // جلب الطلبات الخاصة بالمستخدم الحالي فقط
            $requests = MaterialRequest::with(['manager', 'requestedBy', 'warehouseKeeper'])
                ->where('requested_by', $user->id) // فلترة حسب المستخدم الحالي
                ->orderBy('created_at', 'desc') // ترتيب حسب التاريخ الأحدث
                ->get();

            return $this->successResponse($requests, 'تم جلب طلبات المواد الخاصة بك بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'warehouse_keeper_id' => 'required|exists:warehouse_keepers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                $user = $request->user()->load('department.manager');

                if (!$user || !$user->department || !$user->department->manager) {
                    // رجّع استثناء بدل response
                    throw new \Exception('المستخدم أو المدير غير موجود');
                }

                $manager = $user->department->manager;

                $serialNumber = 'MR-' . date('YmdHis') . '-' . Str::random(4);

                $materialRequest = MaterialRequest::create([
                    'requested_by' => $user->id,
                    'manager_id' => $manager->id,
                    'warehouse_keeper_id' => $request->warehouse_keeper_id,
                    'status' => 'pending',
                    'serial_number' => $serialNumber,
                    'date' => $request->date,
                ]);

                foreach ($request->items as $item) {
                    MaterialRequestItem::create([
                        'material_request_id' => $materialRequest->id,
                        'product_id' => $item['product_id'],
                        'quantity_requested' => $item['quantity_requested'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // ✨ إنشاء إشعار للمدير
                $this->notificationService->notify(
                    $manager,
                    'طلب مواد جديد',
                    "الموظف {$user->name} أنشأ طلب مواد جديد برقم {$materialRequest->serial_number}",
                    'request_created',
                    $materialRequest->id
                );

                return [
                    'material_request' => $materialRequest->load(['items', 'manager']),
                    'message' => 'تم إنشاء طلب المواد بنجاح وإرسال إشعار للمدير'
                ];
            });

            return $this->successResponse(
                $result['material_request'],
                $result['message'],
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل في إنشاء طلب المواد: ' . $e->getMessage(),
                500,
                ['trace' => $e->getTraceAsString()],
                'MATERIAL_REQUEST_CREATION_FAILED'
            );
        }
    }


    public function pendingRequests(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse(
                    message: 'غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.',
                    code: 401,
                    internalCode: 'UNAUTHORIZED_ACCESS'
                );
            }

            // جلب الطلبات المعلقة المرتبطة بالمدير الحالي فقط
            $pendingRequests = MaterialRequest::with(['manager', 'requestedBy', 'warehouseKeeper'])
                ->withCount('items')
                ->where('status', 'pending')
                ->where('manager_id', $user->id) // فلترة حسب المدير الحالي
                ->orderBy('created_at', 'desc') // الأحدث أولاً
                ->get();

            $formatted = $pendingRequests->map(function ($r) {
                return [
                    'id' => $r->id,
                    'serial_number' => $r->serial_number,
                    'date' => $r->date,
                    'requested_by' => $r->requestedBy->name,
                    'warehouse_keeper' => $r->warehouseKeeper->name,
                    'items_count' => $r->items_count,
                    'created_at' => $r->created_at->format('Y-m-d H:i'),
                    'manager' => [
                        'id' => $r->manager->id,
                        'name' => $r->manager->name,
                        'email' => $r->manager->email ?? null
                    ]
                ];
            });

            return $this->successResponse($formatted, 'تم جلب الطلبات المعلقة المرتبطة بك بنجاح');

        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    public function approveRequest($id)
    {
        try {
            DB::beginTransaction();
            $requestModel = MaterialRequest::with(['items', 'manager', 'requestedBy'])->find($id);

            if (!$requestModel) {
                return $this->errorResponse('الطلب غير موجود', 404, [], 'REQUEST_NOT_FOUND');
            }

            if ($requestModel->status != 'pending') {
                return $this->errorResponse('لا يمكن الموافقة على طلب غير معلق', 422, [], 'INVALID_STATUS');
            }

            // تحديث حالة الطلب
            $requestModel->update(['status' => 'approved']);

            // تحديث العناصر
            foreach ($requestModel->items as $item) {
                $item->update([
                    'quantity_approved' => $item->quantity_requested,
                    'approval_notes' => $item->notes ?? null
                ]);
            }

         // ✨ إرسال إشعار لليوزر/////////////////
            $this->notificationService->notify(
                $requestModel->requestedBy, // اليوزر يلي قدّم الطلب
                'تمت الموافقة على طلبك',
                "وافق المدير على طلبك رقم {$requestModel->serial_number}، يرجى التوجه إلى أمين المستودع لاستلام المواد.",
                'request_approved',
                $requestModel->id
            );

            // ✨ إشعار أمين المستودع
            $warehouseKeeper = $requestModel->requestedBy?->department?->warehouse?->warehouseKeeper;

            if ($warehouseKeeper) {
                $this->notificationService->notify(
                    $warehouseKeeper,
                    'مطلوب إنشاء مذكرة إخراج',
                    "تمت الموافقة على طلب مواد رقم {$requestModel->serial_number}. يرجى إنشاء مذكرة إخراج للطلب  " ,
                    'request_to_release',
                    $requestModel->id
                );
            }


            DB::commit();
            return $this->successResponse($requestModel, 'تمت الموافقة على الطلب وإرسال إشعار للموظف و إشعار لأمين المستودع', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'فشل في الموافقة على الطلب: ' . $e->getMessage(),
                500,
                ['trace' => $e->getTraceAsString()],
                'APPROVAL_FAILED'
            );
        }
    }

    public function editRequest($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:material_request_items,id,material_request_id,' . $id,
            'items.*.quantity_approved' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();
            $requestModel = DB::transaction(function () use ($id, $request) {
                $requestModel = MaterialRequest::with(['items', 'requestedBy'])->find($id);

                if (!$requestModel) {
                    throw new \Exception('الطلب غير موجود');
                }

                if ($requestModel->status != 'pending') {
                    throw new \Exception('لا يمكن تعديل طلب غير معلق');
                }

                $requestModel->update([
                    'status'        => 'approved',
                    'approved_by'   => auth()->id(), // المدير الحالي
                    'approved_at'   => now(),
                    'manager_notes' => $request->notes
                ]);

                // ✅ نمرّ على كل المواد ونحدثها
                foreach ($requestModel->items as $item) {
                    $itemData = collect($request->items)->firstWhere('id', $item->id);

                    if ($itemData) {
                        // تعديل من المدير
                        if ($itemData['quantity_approved'] > $item->quantity_requested) {
                            throw new \Exception("الكمية المعتمدة لا يمكن أن تكون أكبر من المطلوبة للمادة: {$item->product->name}");
                        }

                        $item->update([
                            'quantity_approved' => $itemData['quantity_approved'],
                            'approval_notes'    => $itemData['notes'] ?? null
                        ]);
                    } else {
                        // ما في تعديل → نوافق بالكمية الأصلية المطلوبة
                        $item->update([
                            'quantity_approved' => $item->quantity_requested,
                            'approval_notes'    => null
                        ]);
                    }
                }

                return $requestModel;
            });

            // ✨ إرسال إشعار للموظف
            $this->notificationService->notify(
                $requestModel->requestedBy,
                'تم تعديل طلبك',
                "وافق المدير على طلبك رقم {$requestModel->serial_number} لكن عدّل بعض الكميات. يرجى التوجه إلى أمين المستودع لاستلام المواد.",
                'request_edited',
                $requestModel->id
            );

            // ✨ إشعار أمين المستودع
            $warehouseKeeper = $requestModel->requestedBy?->department?->warehouse?->warehouseKeeper;

            if ($warehouseKeeper) {
                $this->notificationService->notify(
                    $warehouseKeeper,
                    'مطلوب إنشاء مذكرة إخراج',
                    "تمت الموافقة على طلب مواد رقم {$requestModel->serial_number}. يرجى إنشاء مذكرة إخراج للطلب " ,
                    'request_to_release',
                    $requestModel->id
                );
            }


            DB::commit();
            return $this->successResponse(
                MaterialRequest::with(['items.product'/*, 'approvedBy'*/, 'manager', 'requestedBy'])->find($id),
                'تم تعديل والموافقة على طلب المواد بنجاح وإرسال إشعار للموظف و إشعار لأمين المستودع'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'فشل في اعتماد الطلب: ' . $e->getMessage(),
                422,
                ['trace' => $e->getTraceAsString()],
                'APPROVAL_FAILED'
            );
        }
    }


    public function rejectRequest($id)
    {
        try {
            $requestModel = DB::transaction(function () use ($id) {
                $requestModel = MaterialRequest::with(['requestedBy', 'items', 'manager'])->find($id);

                if (!$requestModel) {
                    throw new \Exception('الطلب غير موجود');
                }

                if ($requestModel->status != 'pending') {
                    throw new \Exception('لا يمكن رفض طلب غير معلق');
                }

                $requestModel->update([
                    'status'      => 'rejected',
                    'approved_by' => auth()->id(), // المدير الحالي
                    'approved_at' => now(),
                ]);

                return $requestModel;
            });

            // ✨ إرسال إشعار للموظف
            $this->notificationService->notify(
                $requestModel->requestedBy,
                'تم رفض طلبك',
                "عذراً، لقد تم رفض طلب المواد الخاص بك رقم {$requestModel->serial_number} من قبل المدير.",
                'request_rejected',
                $requestModel->id
            );

            return $this->successResponse(
                MaterialRequest::with(['manager', 'requestedBy', 'items.product'])->find($id),
                " تم رفض طلب المواد بنجاح و إرسال إشعار للموظف"
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل في رفض الطلب: ' . $e->getMessage(),
                422,
                ['trace' => $e->getTraceAsString()],
                'REJECTION_FAILED'
            );
        }
    }

    public function showRequest($id)
    {
        try {
            $requestModel = MaterialRequest::with([
                'items.product',
                'requestedBy',
                'warehouseKeeper',
                'manager',
            ])->find($id);

            if (!$requestModel) {
                return $this->errorResponse('الطلب غير موجود', 404, [], 'REQUEST_NOT_FOUND');
            }

            return $this->successResponse($requestModel, 'تم جلب تفاصيل الطلب بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'فشل في جلب تفاصيل الطلب: ' . $e->getMessage(),
                422,
                ['trace' => $e->getTraceAsString()],
                'SHOW_REQUEST_FAILED'
            );
        }
    }

    public function userRequests($userId)
    {
        try {
            $requests = MaterialRequest::with(['manager', 'requestedBy', 'warehouseKeeper'])
                ->where('requested_by', $userId) // فلترة حسب المستخدم
                ->get();

            return $this->successResponse(
                $requests,
                'تم جلب الطلبات الخاصة بالمستخدم بنجاح'
            );
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


}

