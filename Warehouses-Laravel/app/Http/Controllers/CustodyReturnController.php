<?php

namespace App\Http\Controllers;

use App\Models\CustodyItem;
use App\Models\CustodyReturn;
use App\Models\CustodyReturnItem;
use App\Models\EntryNote;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ProductMovement;
use App\Models\Stock;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustodyReturnController extends Controller
{
    use ApiResponse;
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function createReturnRequest(Request $request)
    {
        $user = $request->user();
        if ($request->has('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $custodyItemId = $item['custody_item_id'] ?? null;
                if (!$custodyItemId) {
                    continue; // سيتم التعامل مع هذا الخطأ في الـ Validator الرئيسي
                }

                $existsInPendingReturn = CustodyReturnItem::where('custody_item_id', $custodyItemId)
                    ->whereHas('custodyReturn', fn($q) => $q->where('status', 'pending'))
                    ->where('warehouse_manager_status', 'pending_review')
                    ->exists();

                if ($existsInPendingReturn) {
                    $custodyItem = CustodyItem::with('product')->find($custodyItemId);
                    $productName = $custodyItem ? $custodyItem->product->name : 'العنصر المحدد';
                    $errorMessage = "{$productName} قيد الإرجاع حالياً في طلب آخر بانتظار المراجعة.";

                    // إرجاع استجابة خطأ مخصصة فوراً
                    return $this->errorResponse(
                        'فشل في إنشاء طلب إرجاع العهدة: ' . $errorMessage,
                        422
                    );
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'return_date' => 'required|date|before_or_equal:today',
            'notes'       => 'nullable|string|max:1000',
            'items'       => 'required|array|min:1',
            'items.*.custody_item_id' => [
                'required',
                'exists:custody_items,id',
                // التحقق من أن العنصر يخص المستخدم وليس مستهلكاً وغير مضاف لطلب إرجاع آخر
                function ($attribute, $value, $fail) use ($user) {
                    $custodyItem = CustodyItem::with('product', 'custody')->find($value);

                    if (!$custodyItem) {
                        $fail('عنصر العهدة غير موجود.');
                        return;
                    }

                    // التحقق من أن عنصر العهدة يخص المستخدم المصادق عليه
                    if ($custodyItem->custody->user_id !== $user->id) {
                        $fail("عنصر العهدة (ID: {$custodyItem->id}) لا يخص المستخدم الحالي.");
                        return;
                    }

                    // التحقق من أن العنصر ليس مستهلكاً
                    if ($custodyItem->product->consumable) {
                        $fail("لا يمكن إرجاع المنتج المستهلك ({$custodyItem->product->name}).");
                        return;
                    }

                    // التحقق من أن العنصر ليس قيد المراجعة في طلب إرجاع آخر
                    $existsInPendingReturn = CustodyReturnItem::where('custody_item_id', $value)
                        ->whereHas('custodyReturn', function ($q) {
                            $q->where('status', 'pending');
                        })
                        ->where('warehouse_manager_status', 'pending_review')
                        ->exists();

                    if ($existsInPendingReturn) {
                        $fail("{$custodyItem->product->name} قيد الإرجاع حالياً في طلب آخر بانتظار المراجعة.");
                        return;
                    }
                },
            ],
            'items.*.returned_quantity' => [
                'required',
                'numeric',
                'min:0.01',
                // التحقق من أن الكمية المرتجعة لا تتجاوز الكمية المتاحة
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $custodyItemId = $request->input("items.{$index}.custody_item_id");
                    $custodyItem = CustodyItem::find($custodyItemId);

                    if (!$custodyItem) {
                        $fail('عنصر العهدة غير موجود.');
                        return;
                    }

                    // حساب الكمية المرتجعة سابقاً (المقبولة)
                    $previouslyReturnedQuantity = CustodyReturnItem::where('custody_item_id', $custodyItemId)
                        ->where('warehouse_manager_status', 'accepted')
                        ->sum('returned_quantity');

                    $availableQuantity = $custodyItem->quantity - $previouslyReturnedQuantity;

                    if ($value > $availableQuantity) {
                        $fail("الكمية المرتجعة لـ {$custodyItem->product->name} (ID: {$custodyItem->id}) لا يمكن أن تتجاوز الكمية المتاحة للإرجاع ({$availableQuantity}).");
                    }
                },
            ],
            'items.*.user_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // --- 2. تنفيذ العملية داخل Transaction ---
        DB::beginTransaction();

        try {
            $serial =$this->generateSerialNumber();
            // إنشاء طلب الإرجاع الرئيسي
            $custodyReturn = CustodyReturn::create([
                'user_id'       => $user->id,
                'return_date'   => $request->return_date,
                'serial_number' => $serial,
                'notes'         => $request->notes,
                'status'        => 'pending',
            ]);

            // تجهيز بيانات عناصر الإرجاع لإدخالها دفعة واحدة
            $returnItemsToInsert = [];
            foreach ($request->items as $item) {
                $returnItemsToInsert[] = [
                    'custody_return_id'        => $custodyReturn->id,
                    'custody_item_id'          => $item['custody_item_id'],
                    'returned_quantity'        => $item['returned_quantity'],
                    'warehouse_id'             => $user->department->warehouse_id,
                    'user_notes'               => $item['user_notes'] ?? null,
                    'warehouse_manager_status' => 'pending_review',
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];
            }

            // إدخال جماعي للعناصر
            CustodyReturnItem::insert($returnItemsToInsert);


            // --- 3. تجهيز وإرسال الاستجابة الناجحة ---
            $custodyReturn->load('items.custodyItem.product', 'items.warehouse', 'user');

            $manager = $user->department->manager;
            if (!$manager) {
                throw new \Exception('لا يوجد مدير مرتبط بالقسم.');
            }
            // 🔔 إشعار المدير
            if ($manager && isset($this->notificationService)) {
                $this->notificationService->notify(
                    $manager,
                    'طلب إرجاع عهدة',
                    "يوجد طلب إرجاع عهدة طلب مواد جديد برقم {$custodyReturn->serial_number}",
                    'custody-return-request',
                    $custodyReturn->id
                );
            }
            DB::commit();
            return $this->successResponse(
                $custodyReturn,
                'تم إنشاء طلب إرجاع العهدة بنجاح. بانتظار مراجعة أمين المستودع.'
            );

        } catch (\Throwable $e) {
            DB::rollBack();

            // --- 4. إرسال استجابة الخطأ في حال الفشل ---
            return $this->errorResponse(
                'فشل في إنشاء طلب إرجاع العهدة: ' . $e->getMessage(),
                422
            );
        }
    }

    public function processCustodyReturnItem(Request $request, int $custodyReturnItemId)
    {
        $user = $request->user();

        $custodyReturnItem = CustodyReturnItem::with('custodyReturn', 'custodyItem.product', 'warehouse')
            ->find($custodyReturnItemId);

        if (!$custodyReturnItem) {
            return $this->notFoundResponse('عنصر طلب الإرجاع غير موجود');
        }

        if ($custodyReturnItem->warehouse_manager_status !== 'pending_review') {
            return $this->errorResponse('هذا العنصر تمت معالجته مسبقاً', 400);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_manager_status' => [
                'required',
                Rule::in(['accepted', 'rejected', 'damaged', 'total_loss']),
            ],
            'warehouse_manager_notes' => 'nullable|string|max:1000',
            'returned_quantity_accepted' => [
                Rule::requiredIf($request->input('warehouse_manager_status') === 'accepted'),
                'numeric',
                'min:0',
                'max:' . $custodyReturnItem->returned_quantity,
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        DB::beginTransaction();

        try {
            $newStatus = $request->input('warehouse_manager_status');
            $notes = $request->input('warehouse_manager_notes');
            $acceptedQuantity = $request->input('returned_quantity_accepted', 0);

            $custodyReturnItem->warehouse_manager_status = $newStatus;
            $custodyReturnItem->warehouse_manager_notes = $notes;
            $custodyReturnItem->returned_quantity_accepted = $acceptedQuantity;
            $custodyReturnItem->save();

            // تحديث حالة طلب الإرجاع الرئيسي إذا اكتمل
            $custodyReturn = $custodyReturnItem->custodyReturn;
            $allReturnItems = $custodyReturn->items;

            $allProcessed = $allReturnItems->every(fn($item) => $item->warehouse_manager_status !== 'pending_review');

            if ($allProcessed) {
                $hasIssues = $allReturnItems->some(fn($item) => in_array($item->warehouse_manager_status, ['rejected', 'damaged', 'total_loss']));

                $custodyReturn->status = $hasIssues ? 'partially_completed' : 'completed';
                $custodyReturn->save();
            }

            DB::commit();
            return $this->successResponse(
                $custodyReturnItem->load('custodyReturn', 'custodyItem.product', 'warehouse'),
                'تم إنشاء طلب إرجاع العهدة بنجاح. بانتظار مراجعة أمين المستودع.'
            );


        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(
                'فشل في معالجة عنصر الإرجاع: ' . $e->getMessage(),
                500
            );
        }
    }


    public function index()
    {

        $custodyReturns = CustodyReturn::with([
            'user', // المستخدم الذي قدم الطلب
            'items.custodyItem.product', // تفاصيل المنتج الأصلي
            'items.warehouse', // المستودع الذي تم الإرجاع إليه
        ])
            ->latest() // ترتيب حسب الأحدث
            ->get();

        // معالجة حالة عدم وجود طلبات
        if ($custodyReturns->isEmpty()) {
            return $this->successResponse([],'لا توجد طلبات إرجاع حالياً.'); // إرجاع مصفوفة فارغة
        }
        return $this->successResponse($custodyReturns,'تم استرجاع طلبات الإرجاع بنجاح.' );
    }

    public function show(int $id)
    {
        $user = Auth::user();


        $custodyReturn = CustodyReturn::with([
            'user',
            'items.custodyItem.product',
            'items.warehouse',
        ])->find($id);

        if (!$custodyReturn) {
            return $this->notFoundResponse('طلب الإرجاع غير موجود.');
        }

        return $this->successResponse($custodyReturn,'تم استرجاع طلب الإرجاع بنجاح.');
    }

    public function myReturnRequests(Request $request)
    {
        $user = $request->user();


        $myCustodyReturns = CustodyReturn::where('user_id', $user->id)
            ->with([
                // لا نحتاج لتحميل 'user' هنا لأنه هو نفسه المستخدم الحالي
                'items.custodyItem.product',
                'items.warehouse',
            ])
            ->latest()
            ->get();

        // معالجة حالة عدم وجود طلبات
        if ($myCustodyReturns->isEmpty()) {
            return $this->successResponse([],'لا توجد طلبات إرجاع خاصة بك حالياً.'); // إرجاع مصفوفة فارغة
        }

        return $this->successResponse($myCustodyReturns,'تم استرجاع طلبات الإرجاع الخاصة بك بنجاح.');
    }

    public function pendingReturnRequests(Request $request)
    {
        $user = $request->user();


        // نبحث عن CustodyReturnItem التي حالتها 'pending_review'
        // ثم نحمل الـ CustodyReturn الرئيسي المرتبط بها والمستخدم.
        $pendingItems = CustodyReturnItem::where('warehouse_manager_status', 'pending_review')
            ->with([
                'custodyReturn.user', // طلب الإرجاع الرئيسي والمستخدم الذي قدمه
                'custodyItem.product', // تفاصيل المنتج الأصلي للعهدة
                'warehouse', // المستودع الذي من المفترض أن تعود إليه
            ])
            ->latest() // ترتيب حسب الأحدث
            ->get();

        // معالجة حالة عدم وجود طلبات
        if ($pendingItems->isEmpty()) {
            return $this->successResponse([],'لا توجد طلبات إرجاع معلقة حالياً.'); // إرجاع مصفوفة فارغة
        }

        return $this->successResponse($pendingItems,'تم استرجاع طلبات الإرجاع المعلقة بنجاح.');
    }


    private function generateSerialNumber(): string
    {
        $currentYear = date('Y');

        // الحصول على آخر مذكرة لهذه السنة
        $lastCo = CustodyReturn::whereYear('created_at', $currentYear)
            ->orderBy('id', 'desc')
            ->first();

        // تحديد الأرقام الجديدة
        if (!$lastCo) {
            // أول مذكرة في السنة
            $folderNumber = 1;
            $noteNumber = 1;
        } else {
            // فك الترميز من السيريال السابق
            $serial = trim($lastCo->serial_number, '()');
            list($lastFolderNumber, $lastNoteNumber) = explode('/', $serial);

            $lastFolderNumber = (int)$lastFolderNumber;
            $lastNoteNumber = (int)$lastNoteNumber;

            // حساب الأرقام الجديدة
            $noteNumber = $lastNoteNumber + 1;
            $folderNumber = $lastFolderNumber;

            if ($noteNumber % 50 == 1 && $noteNumber > 50) {
                $folderNumber = floor($noteNumber / 50) + 1;
            }
        }

        return "($folderNumber/$noteNumber)";
    }


}






































































//        $user = Auth::user();
//
//        if (!$user) {
//            return $this->unauthorizedResponse('الرجاء تسجيل الدخول.');
//        }
//
//        $validator = Validator::make($request->all(), [
//            'return_date' => 'required|date|before_or_equal:today',
//            'notes' => 'nullable|string|max:1000',
//            'items' => 'required|array|min:1',
//            'items.*.custody_item_id' => [
//                'required',
//                'integer',
//                'exists:custody_items,id', // فقط التأكد أن العنصر موجود
//                // التحقق من أن عنصر العهدة هذا يخص المستخدم المصادق عليه
//                Rule::exists('custody_items', 'id')->where(function ($query) use ($user) {
//                    $query->whereHas('custody', function ($q) use ($user) {
//                        $q->where('user_id', $user->id);
//                    });
//                }),
//                // التحقق من أن العنصر ليس مستهلكاً
//                function ($attribute, $value, $fail) {
//                    $custodyItem = CustodyItem::find($value);
//                    if (!$custodyItem) { // للتأكد أنه تم العثور على العنصر
//                        $fail('عنصر العهدة غير موجود.');
//                        return;
//                    }
//                    if ($custodyItem->product->consumable) {
//                        $fail("لا يمكن إرجاع المنتج المستهلك ({$custodyItem->product->name}).");
//                    }
//                },
//
//                function ($attribute, $value, $fail) {
//                    $existsInPendingReturn = CustodyReturnItem::where('custody_item_id', $value)
//                        ->whereHas('custodyReturn', function($q) {
//                            $q->where('status', 'pending'); // أو 'processing' إذا كان لديك هذه الحالة لطلب الإرجاع
//                        })
//                        ->where('warehouse_manager_status', 'pending_review') // فقط إذا كان العنصر نفسه قيد المراجعة
//                        ->exists();
//
//                    if ($existsInPendingReturn) {
//                        $custodyItem = CustodyItem::find($value); // لجلب اسم المنتج للرسالة
//                        $productName = $custodyItem ? $custodyItem->product->name : 'هذا العنصر';
//                        $fail("{$productName} قيد الإرجاع حالياً في طلب آخر بانتظار المراجعة.");
//                    }
//                },
//            ],
//            'items.*.returned_quantity' => [
//                'required',
//                'numeric',
//                'min:0.01',
//                // التحقق من أن الكمية المرتجعة لا تتجاوز الكمية الأصلية المتبقية
//                function ($attribute, $value, $fail) use ($request, $user) {
//                    $index = explode('.', $attribute)[1];
//                    $custodyItemId = $request->input("items.{$index}.custody_item_id");
//
//                    $custodyItem = CustodyItem::where('id', $custodyItemId)
//                        ->whereHas('custody', function($q) use ($user) {
//                            $q->where('user_id', $user->id);
//                        })
//                        ->first();
//
//                    if (!$custodyItem) {
//                        $fail('عنصر العهدة غير موجود أو لا ينتمي للمستخدم الحالي.');
//                        return;
//                    }
//
//                    // حساب الكمية المرجعة سابقاً (وتم قبولها)
//                    // يجب أن نأخذ في الاعتبار فقط الكميات التي تم قبولها بنجاح كمرتجعة
//                    $previouslyReturnedQuantity = CustodyReturnItem::where('custody_item_id', $custodyItemId)
//                        ->where('warehouse_manager_status', 'accepted') // أو 'returned' حسب الحالة النهائية
//                        ->sum('returned_quantity');
//
//                    $availableQuantity = $custodyItem->quantity - $previouslyReturnedQuantity;
//
//                    if ($value > $availableQuantity) {
//                        $fail("الكمية المرتجعة لـ {$custodyItem->product->name} (ID: {$custodyItem->id}) لا يمكن أن تتجاوز الكمية المتاحة للإرجاع ({$availableQuantity}).");
//                    }
//                },
//            ],
//            'items.*.warehouse_id' => 'required|exists:warehouses,id',
//            'items.*.user_notes' => 'nullable|string|max:500',
//        ]);
//
//        if ($validator->fails()) {
//            return $this->validationErrorResponse($validator);
//        }
//
//        DB::beginTransaction();
//
//        try {
//            // إنشاء طلب الإرجاع الرئيسي
//            $custodyReturn = CustodyReturn::create([
//                'user_id' => $user->id,
//                'return_date' => $request->return_date,
//                'notes' => $request->notes,
//                'status' => 'pending', // الحالة الأولية لطلب الإرجاع
//            ]);
//
//            $returnItemsToInsert = [];
//            foreach ($request->items as $item) {
//                // جلب CustodyItem هنا ليس فقط للتحقق، بل للحصول على بياناته مثل product
//                $custodyItem = CustodyItem::with('product')->find($item['custody_item_id']);
//
//                $returnItemsToInsert[] = [
//                    'custody_return_id' => $custodyReturn->id,
//                    'custody_item_id' => $item['custody_item_id'],
//                    'returned_quantity' => $item['returned_quantity'],
//                    'warehouse_id' => $item['warehouse_id'],
//                    'user_notes' => $item['user_notes'] ?? null,
//                    'warehouse_manager_status' => 'pending_review', // حالة أولية لعنصر الإرجاع
//                    'created_at' => now(),
//                    'updated_at' => now(),
//                ];
//
//            }
//
//            CustodyReturnItem::insert($returnItemsToInsert); // إدخال جماعي للعناصر
//
//            DB::commit();
//
//            // تحميل العلاقات للاستجابة
//            $custodyReturn->load('items.custodyItem.product', 'items.warehouse', 'user');
//
//            return $this->successResponse(
//                'تم إنشاء طلب إرجاع العهدة بنجاح. بانتظار مراجعة أمين المستودع.',
//                $custodyReturn
//            );
//
//        } catch (\Throwable $e) {
//            DB::rollBack();
//            return $this->errorResponse(
//                'فشل في إنشاء طلب إرجاع العهدة: ' . $e->getMessage(),
//                422
//            );
//        }
//    }





