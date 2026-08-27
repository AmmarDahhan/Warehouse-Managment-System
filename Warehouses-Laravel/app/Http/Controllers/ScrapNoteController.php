<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExitNote;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ProductMovement;
use App\Models\ScrapNote;
use App\Models\ScrappedMaterial;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseKeeper;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\InventoryService;

class ScrapNoteController extends Controller
{
    //
    use ApiResponse;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // إظهار مذكرات الإتلاف للمستخدم الحالي
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

            // جلب مذكرات الإتلاف الخاصة بالمستخدم الحالي فقط
            $notes = ScrapNote::with(['materials.product', 'createdBy', 'approvedBy'])
                ->withCount('materials as materials_count')
                ->where('created_by', $user->id) // فلترة حسب المستخدم الحالي
                ->orderBy('created_at', 'desc') // ترتيب حسب التاريخ الأحدث
                ->get();

            return $this->successResponse($notes, 'تم جلب مذكرات الإتلاف الخاصة بك بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }



    public function store(Request $request)
    {
        $user =  $request->user();

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'materials' => 'required|array|min:1',
            'materials.*.product_id' => 'required|exists:products,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $scrapNote = null;
            $locationMessages = [];

            DB::transaction(function () use ($request, &$scrapNote, &$locationMessages) {
                // إنشاء مذكرة التلف
                $scrapNote = ScrapNote::create([
                    'created_by' => $request->user()->id,
                    'approved_by' => null,
                    'serial_number' => $this->generateSerialNumber(),
                    'reason' => $request->reason,
                    'date' => $request->date,
                    'notes' => $request->notes,
                ]);

                // إضافة المواد التالفة فقط
                foreach ($request->materials as $material) {
                    $product = Product::findOrFail($material['product_id']);
                    $quantity = $material['quantity'];

                    ScrappedMaterial::create([
                        'scrap_note_id' => $scrapNote->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'notes' => $material['notes'] ?? null,
                    ]);

                    // فقط رسالة توضيحية (بدون تخزين location_id)
                    $locationMessages[] = "تم إتلاف {$quantity} {$product->unit} من المنتج '{$product->name}'.";
                }
            });

            try {
                // إيجاد المدير عبر العلاقات: أمين المستودع -> مستودع -> قسم -> مدير
                $warehouseKeeper = WarehouseKeeper::with('warehouses.department.manager')
                    ->where('id', $user->id)
                    ->first();

                if (!$warehouseKeeper) {
                    throw new \Exception('لا يوجد أمين مستودع بهذا المعرف.');
                }

                // البحث عن المستودع - إما من warehouse_id المرسل أو أول مستودع متاح
                $warehouseId = $request->warehouse_id ?? null;
                $warehouse = null;

                if ($warehouseId) {
                    // البحث عن المستودع المحدد
                    $warehouse = $warehouseKeeper->warehouses()
                        ->where('id', $warehouseId)
                        ->with('department.manager')
                        ->first();
                    
                    if (!$warehouse) {
                        throw new \Exception('المستودع المحدد غير موجود أو غير مرتبط بك.');
                    }
                } else {
                    // أخذ أول مستودع متاح
                    $warehouse = $warehouseKeeper->warehouses()
                        ->with('department.manager')
                        ->first();
                    
                    if (!$warehouse) {
                        throw new \Exception('لا يوجد مستودع مرتبط بأمين المستودع.');
                    }
                }

                $department = $warehouse->department;
                if (!$department) {
                    throw new \Exception('المستودع "' . $warehouse->name . '" غير مرتبط بأي قسم.');
                }

                $manager = $department->manager;
                if (!$manager) {
                    throw new \Exception('القسم "' . $department->name . '" لا يحتوي على مدير.');
                }

            } catch (\Exception $e) {
                return $this->errorResponse(
                    'فشل في تحديد مدير القسم المرتبط بأمين المستودع: ' . $e->getMessage(),
                    404,
                    [],
                    'MANAGER_NOT_FOUND'
                );
            }


            // 🔔 إشعار المدير
            if ($manager && isset($this->notificationService)) {
                $this->notificationService->notify(
                    $manager,
                    'طلب إتلاف مواد جديد',
                    'يوجد طلب إتلاف مواد جديد بانتظار المراجعة (رقم: ' . $scrapNote->serial_number . ')',
                    'scrap-note',
                    $scrapNote->id
                );
            }

            return $this->successResponse(
                [
                    'scrap_note' => $scrapNote->load('materials'),
                    'location_messages' => $locationMessages,
                ],
                'تم إنشاء مذكرة التلف بنجاح وسوف يتم مراجعتها للموافقة'
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse(
                message: 'فشل في إنشاء مذكرة التلف: ' . $e->getMessage(),
                code: 422,
                internalCode: 'SCRAP_NOTE_CREATION_FAILED'
            );
        }
    }


    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        try {
            DB::transaction(function () use ($id, $request, $user) {
                $scrapNote = ScrapNote::with(['materials.product', 'createdBy.warehouses.department'])
                    ->findOrFail($id);

                // ✅ التحقق من صلاحية المدير - أمان إضافي
                $hasAuthority = $scrapNote->createdBy->warehouses->contains(function($warehouse) use ($user) {
                    return $warehouse->department && $warehouse->department->manager_id === $user->id;
                });

                if (!$hasAuthority) {
                    throw new \Exception('غير مصرح لك بالموافقة على هذه المذكرة.');
                }

                if ($scrapNote->status != ScrapNote::STATUS_PENDING) {
                    throw new \Exception('لا يمكن الموافقة على مذكرة تلف غير معلقة.');
                }

                // ... existing code ...
                foreach ($scrapNote->materials as $material) {
                    $productId = $material->product_id;

                    // ابحث عن موقع فيه الكمية المطلوبة
                    $productLocation = ProductLocation::where('product_id', $material->product_id)
                        ->where('quantity', '>=', $material->quantity)
                        ->first();

                    if (!$productLocation) {
                        throw new \Exception("الكمية المطلوبة ({$material->quantity}) من المنتج '{$material->name}' غير متوفرة في أي موقع.");
                    }

                    $location = $productLocation->location;

                    // خَصْم الكَمّيَّة
                    $productLocation->decrement('quantity', $material->quantity);
                    $location->decrement('used_capacity_units', $material->quantity);

                    // رسالة للمستخدم
                    $locationMessages[] = "تم خصم {$material->quantity} {$material->unit} من المنتج '{$material->name}' من الموقع '{$location->name}'.";


                    $quantityToScrap = $material->quantity; // <--- استخدام الكمية الأصلية التي طلبها أمين المستودع
                    $locationId = $material->location_id;   // <--- استخدام الـ location_id المخزن أصلاً

                    // إذا كانت الكمية الأصلية صفر أو أقل، لا نقوم بأي خصم أو حركة لهذه المادة
                    if ($quantityToScrap <= 0) {
                        $material->update(['quantity_approved' => 0]);
                        continue;
                    }


                    // تحديث عنصر ScrappedMaterial بالكمية الأصلية كموافقة عليها
                    $material->update([
                        'quantity_approved' => $quantityToScrap, // الكمية المعتمدة هي نفسها الكمية المطلوبة
                    ]);



                    // تحديث المخزون الإجمالي (Stocks)
                    $stock = Stock::firstOrCreate(
                        ['product_id' => $productId, 'warehouse_id' => $location->warehouse_id],
                        ['quantity' => 0]
                    );

                    $prvQuantity = $stock->quantity;
                    $stock->decrement('quantity', $quantityToScrap);

                    // تسجيل حركة المنتج (ProductMovement)
                    ProductMovement::create([
                        'product_id' => $productId,
                        'warehouse_id' => $location->warehouse_id,
                        'type' => 'scrap',
                        'reference_serial' => $scrapNote->serial_number,
                        'prv_quantity' => $prvQuantity,
                        'note_quantity' => $quantityToScrap,
                        'after_quantity' => $stock->quantity,
                        'date' => now(),
                        'reference_type' => 'ScrappedMaterial',
                        'reference_id' => $material->id,
                        'user_id' => $user->id,
                        'notes' => 'إتلاف المنتج ' . $material->product->name . ' بناءً على موافقة مذكرة التلف ' . $scrapNote->serial_number,
                    ]);
                }

                // تحديث حالة مذكرة التلف
                $scrapNote->update([
                    'status' => ScrapNote::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                // 🔔 إشعار للـ warehouseKeeper (المنشئ)
                $creator =$scrapNote->createdBy;
                if ($creator) {
                    $this->notificationService->notify(
                        $creator,
                        'الموافقة على طلب إتلاف مواد',
                        'تمت الموافقة على طلب إتلاف مواد الخاص بك (رقم: ' .$scrapNote->serial_number . ').',
                        'scrap-note',
                        $scrapNote->id
                    );
                }

            });

            return $this->successResponse(
                null,'تمت الموافقة على مذكرة التلف وتنقيص الكميات بنجاح وإرسال إشعار لأمين المستودع .'
            );
        } catch (\Throwable $e) { // استخدام Throwable لأخطاء أوسع
            DB::rollBack();

            // تحقق إذا كان التطبيق في وضع التصحيح (Debug Mode)
            if (config('app.debug')) {
                //  في بيئة التطوير: أرجع الخطأ بالتفصيل الكامل
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ: ' . $e->getMessage(),
                    'file' => $e->getFile(), // <-- ملف الخطأ
                    'line' => $e->getLine(), // <-- سطر الخطأ
                    'trace' => $e->getTraceAsString() // <-- تتبع مسار الخطأ (اختياري لكن مفيد جداً)
                ], 500); // 500 هو رمز الخطأ الأنسب للخوادم
            }

            // في بيئة الإنتاج: أرجع رسالة عامة وآمنة
            return $this->errorResponse(
                message: 'فشل في الموافقة على المذكرة، حدث خطأ غير متوقع.',
                code: 500, // استخدم 500 Internal Server Error
                internalCode: 'SCRAP_NOTE_APPROVAL_FAILED'
            );
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse(
                    message: 'غير مصرح بالوصول. يرجى تسجيل الدخول أولاً.',
                    code: 401,
                    internalCode: 'UNAUTHORIZED_ACCESS'
                );
            }

            $scrapNote = ScrapNote::with(['createdBy.warehouses.department'])->findOrFail($id);

            // ✅ التحقق من صلاحية المدير - أمان إضافي
            $hasAuthority = $scrapNote->createdBy->warehouses->contains(function($warehouse) use ($user) {
                return $warehouse->department && $warehouse->department->manager_id === $user->id;
            });

            if (!$hasAuthority) {
                throw new \Exception('غير مصرح لك برفض هذه المذكرة.');
            }

            if ($scrapNote->status != ScrapNote::STATUS_PENDING) {
                throw new \Exception("لا يمكن رفض طلب غير معلق ");
            }

            $scrapNote->update([
                'status' => ScrapNote::STATUS_REJECTED,
                'rejection_reason' => $request->rejection_reason,
                'approved_by' =>null /*auth()->id()*/,
                'approved_at' => now(),
            ]);

            // 🔔 إشعار للـ warehouseKeeper (المنشئ)
            $creator =$scrapNote->createdBy;
            if ($creator) {
                $this->notificationService->notify(
                    $creator,
                    'رفض طلب إتلاف مواد',
                    'عذراً تم رفض طلب إتلاف مواد الخاص بك (رقم: ' .$scrapNote->serial_number . ').',
                    'scrap-note',
                    $scrapNote->id
                );
            }

            return $this->successMessage( 'تم رفض مذكرة التلف بنجاح');

        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'فشل في رفض المذكرة' . $e->getMessage(),
                code: 422,
                internalCode: 'SCRAP_NOTE_CREATION_FAILED'
            );
        }
    }

    /**
     * عرض مذكرات الإتلاف المعلقة للمدير الحالي للموافقة عليها حسب التوكن
     */
    public function pendingScrapNotes(Request $request)
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

            // جلب مذكرات الإتلاف المعلقة التي تحتاج موافقة من المدير الحالي
            $pendingScrapNotes = ScrapNote::with([
                'materials.product',   // المنتجات المرتبطة بالمواد التالفة
                'createdBy'            // أمين المستودع الذي أنشأ المذكرة
            ])
            ->withCount('materials as materials_count') // عدد المواد في كل مذكرة
            ->where('status', 'pending') // فقط المذكرات المعلقة
            ->whereNull('approved_by')   // التي لم يتم الموافقة عليها بعد
            ->whereHas('createdBy.warehouses.department', function($query) use ($user) {
                $query->where('manager_id', $user->id); // فقط المذكرات من المستودعات التابعة لهذا المدير
            })
            ->orderBy('created_at', 'asc') // الأقدم أولاً للمراجعة
            ->get();

            // تنسيق البيانات للعرض
            $formattedNotes = $pendingScrapNotes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'serial_number' => $note->serial_number,
                    'date' => $note->date,
                    'created_at' => $note->created_at->format('Y-m-d H:i'),
                    'created_by' => [
                        'id' => $note->createdBy->id,
                        'name' => $note->createdBy->name
                    ],
                    'reason' => $note->reason,
                    'notes' => $note->notes,
                    'materials_count' => $note->materials_count,
                    'materials' => $note->materials->map(function ($material) {
                        return [
                            'id' => $material->id,
                            'product_name' => $material->product->name,
                            'quantity' => $material->quantity,
                            'unit' => $material->product->unit,
                            'notes' => $material->notes
                        ];
                    }),
                    'status' => $note->status,
                    'priority' => $this->calculatePriority($note->created_at),
                    'priority_label' => $this->getPriorityLabel($this->calculatePriority($note->created_at)),
                    'days_pending' => now()->diffInDays($note->created_at)
                ];
            });

            // إحصائيات سريعة
            $statistics = [
                'total_pending' => $formattedNotes->count(),
                'high_priority' => $formattedNotes->where('priority', '>=', 3)->count(),
                'medium_priority' => $formattedNotes->where('priority', '=', 2)->count(),
                'low_priority' => $formattedNotes->where('priority', '=', 1)->count(),
                'total_materials' => $pendingScrapNotes->sum('materials_count')
            ];

            return $this->successResponse([
                'scrap_notes' => $formattedNotes,
                'statistics' => $statistics
            ], 'تم جلب مذكرات الإتلاف المعلقة بنجاح');

        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'فشل في جلب مذكرات الإتلاف المعلقة: ' . $e->getMessage(),
                code: 500,
                internalCode: 'PENDING_SCRAP_NOTES_FETCH_FAILED'
            );
        }
    }

    /**
     * حساب أولوية المذكرة بناءً على عمرها
     */
    private function calculatePriority($createdAt)
    {
        $daysOld = now()->diffInDays($createdAt);

        if ($daysOld >= 3) {
            return 4; // أولوية عالية جداً
        } elseif ($daysOld >= 2) {
            return 3; // أولوية عالية
        } elseif ($daysOld >= 1) {
            return 2; // أولوية متوسطة
        } else {
            return 1; // أولوية منخفضة
        }
    }

    /**
     * الحصول على تسمية الأولوية
     */
    private function getPriorityLabel($priority)
    {
        $labels = [
            1 => 'منخفضة',
            2 => 'متوسطة',
            3 => 'عالية',
            4 => 'عالية جداً'
        ];

        return $labels[$priority] ?? 'غير محدد';
    }

    //إظهار مذكرة محددة للمستخدم الحالي
    public function show($id, Request $request)
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

            // جلب المذكرة الخاصة بالمستخدم الحالي فقط
            $note = ScrapNote::with([
                'materials.product', // تحميل المواد مع معلومات المنتج
                'createdBy:id,name', // معلومات منشئ المذكرة
                'approvedBy:id,name' // معلومات الموافق (إذا موجود)
            ])
            ->where('created_by', $user->id) // فلترة حسب المستخدم الحالي
            ->findOrFail($id);

            return $this->successResponse($note, 'تم جلب المذكرة بنجاح');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('مذكرة الإتلاف غير موجودة أو غير مصرح لك بالوصول إليها.');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e, 'فشل في جلب المذكرة');
        }
    }


    private function generateSerialNumber(): string
    {
        $currentYear = date('Y');

        // الحصول على آخر مذكرة لهذه السنة
        $lastEntry = ScrapNote::whereYear('created_at', $currentYear)
            ->orderBy('id', 'desc')
            ->first();

        // تحديد الأرقام الجديدة
        if (!$lastEntry) {
            // أول مذكرة في السنة
            $folderNumber = 1;
            $noteNumber = 1;
        } else {
            // فك الترميز من السيريال السابق
            $serial = trim($lastEntry->serial_number, '()');
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
