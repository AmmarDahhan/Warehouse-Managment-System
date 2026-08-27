<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntryNote;
use App\Models\EntryNoteItem;
use App\Models\ProductMovement;
use App\Models\ReceivingNote;
use App\Models\ReceivingNoteItem;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use const http\Client\Curl\AUTH_ANY;

class ReceivingNoteController extends Controller
{
    //
    use ApiResponse;

    // إظهار كل المذكرات للمستخدم الحالي فقط
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

            // جلب مذكرات الاستلام الخاصة بالمستخدم الحالي فقط
            $notes = ReceivingNote::withCount('items')
                ->with([
                    'supplier',          // لجلب بيانات المورد المرتبط بالإيصال
                    'purchaseRequest',   // لجلب بيانات طلب الشراء المرتبط بالإيصال
                    'createdBy',         // لجلب بيانات منشئ الإيصال (أمين المستودع)
                    'items.product',     // لجلب تفاصيل المنتج لكل بند
                ])
                ->where('created_by', $user->id) // فلترة حسب المستخدم الحالي
                ->orderBy('created_at', 'desc') // ترتيب حسب التاريخ الأحدث
                ->get();

            return $this->successResponse($notes, 'تم جلب إيصالات الاستلام الخاصة بك بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_requests_id' => 'required|exists:purchase_requests,id', // تمت إضافته
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                // جلب هوية المستخدم بالطريقة الصحيحة وتخزينها
                $keeperId = Auth::guard('warehouse-keeper-api')->id();
                $serialNumber = $this->generateSerialNumber();

                $receivingNote = ReceivingNote::create([
                    'purchase_requests_id' => $request->purchase_requests_id, // تمت إضافته
                    'serial_number' => $serialNumber,
                    'date' => $request->date,
                    'supplier_id' => $request->supplier_id,
                    'created_by' => $keeperId, // استخدام الهوية الصحيحة
                ]);

                foreach ($request->items as $item) {
                    // البحث عن المخزون الحالي قبل أي تعديل (للتسجيل الصحيح)
                    $stockBeforeUpdate = DB::table('stocks')
                        ->where('product_id', $item['product_id'])
                        ->where('warehouse_id', $item['warehouse_id'])
                        ->first();

                    $previousQuantity = $stockBeforeUpdate ? $stockBeforeUpdate->quantity : 0;

                    // استخدام updateOrInsert للتعامل مع المنتجات الجديدة وتحديث القديمة بأمان
                    DB::table('stocks')->updateOrInsert(
                        ['product_id' => $item['product_id'], 'warehouse_id' => $item['warehouse_id']],
                        ['quantity' => DB::raw("quantity + " . $item['quantity'])]
                    );

                    $currentQuantity = $previousQuantity + $item['quantity'];
                    $totalPrice = $item['unit_price'] * $item['quantity'];

                    ReceivingNoteItem::create([
                        'receiving_note_id' => $receivingNote->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'unassigned_quantity' => $item['quantity'], // الكمية المستلمة كلها غير مخصصة بعد
                        'total_price' => $totalPrice,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    ProductMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'],
                        'type' => 'receive',
                        'reference_serial' => $receivingNote->serial_number,
                        'prv_quantity' => $previousQuantity, // القيمة الصحيحة قبل التحديث
                        'note_quantity' => $item['quantity'],
                        'after_quantity' => $currentQuantity, // القيمة الصحيحة بعد التحديث
                        'date' => $request->date,
                        'reference_type' => 'ReceivingNote',
                        'reference_id' => $receivingNote->id,
                        'user_id' => $keeperId, // استخدام الهوية الصحيحة
                        'notes' => $item['notes'] ?? 'استلام من مورد عبر سند رقم: ' . $serialNumber,
                    ]);
                }

                return [
                    'receiving_note' => $receivingNote->load('items.product', 'supplier', 'createdBy'),
                    'message' => 'تم إنشاء إيصال الاستلام بنجاح'
                ];
            });

            return $this->successResponse($result['receiving_note'], $result['message'], 201);
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'فشل في إنشاء إيصال الاستلام: ' . $e->getMessage(),
                code: 500,
                internalCode: 'RECEIVING_NOTE_CREATION_FAILED'
            );
        }
    }

    // إظهار مذكرة محددة مع كل تفاصيلها للمستخدم الحالي
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

            // جلب الإيصال الخاص بالمستخدم الحالي فقط
            $note = ReceivingNote::with([
                'supplier',
                'purchaseRequest',
                'createdBy',
                'items.product',
                'items.warehouse',
            ])
            ->where('created_by', $user->id) // فلترة حسب المستخدم الحالي
            ->findOrFail($id);

            return $this->successResponse($note, 'تم جلب بيانات الإيصال بنجاح');

        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('إيصال الاستلام غير موجود أو غير مصرح لك بالوصول إليه.');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


    //لتوليد السيريال نمبر
    private function generateSerialNumber(): string
    {
        $currentYear = date('Y');

        // الحصول على آخر مذكرة لهذه السنة
        $lastEntry = ReceivingNote::whereYear('created_at', $currentYear)
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
