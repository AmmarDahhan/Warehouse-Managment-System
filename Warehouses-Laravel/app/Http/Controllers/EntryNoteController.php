<?php

namespace App\Http\Controllers;

use App\Models\EntryNote;
use App\Models\EntryNoteItem;
use App\Models\ExitNote;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Stock;

class EntryNoteController extends Controller
{
    use ApiResponse;

    // إظهار مذكرات الإدخال للمستخدم الحالي
    public function index(Request $request)
    {
        try {
            $notes = EntryNote::withCount('items') // هنا نستخدم withCount بدلاً of with
                ->where('created_by', $request->user()->id) // فلترة حسب المستخدم الحالي
                ->with([
                    'warehouse',
                    'createdBy',
                    'user',
                    'items.product',  //  تحميل تفاصيل المنتج لكل عنصر إدخال
                ])
                ->orderBy('created_at', 'desc') // ترتيب حسب التاريخ الأحدث
                ->get();

            return $this->successResponse($notes, 'تم جلب مذكرات الإدخال الخاصة بك بنجاح');
        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                $serialNumber = $this->generateSerialNumber();

                // إنشاء مذكرة إدخال
                $entryNote = EntryNote::create([
                    'serial_number' => $serialNumber,
                    'date' => $request->date,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($request->items as $item) {
                    // إنشاء العناصر بدون موقع
                    $entryNoteItem = EntryNoteItem::create([
                        'entry_note_id' => $entryNote->id,
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'],
                        'quantity' => $item['quantity'],
                        'unassigned_quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                        'created_by' => $request->user()->id,
                    ]);

                    // تحديث المخزون (stocks)
                    DB::table('stocks')->updateOrInsert(
                        ['product_id' => $item['product_id'], 'warehouse_id' => $item['warehouse_id']],
                        ['quantity' => DB::raw('quantity + ' . $item['quantity']), 'updated_at' => now()]
                    );

                    // إنشاء حركة المنتج
                    $afterQuantity = DB::table('stocks')
                        ->where('product_id', $item['product_id'])
                        ->where('warehouse_id', $item['warehouse_id'])
                        ->value('quantity');

                    ProductMovement::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $item['warehouse_id'],
                        'type' => 'entry',
                        'reference_serial' => $entryNote->serial_number,
                        'prv_quantity' => $afterQuantity - $item['quantity'],
                        'note_quantity' => $item['quantity'],
                        'after_quantity' => $afterQuantity,
                        'date' => $request->date,
                        'reference_type' => 'EntryNote',
                        'reference_id' => $entryNote->id,
                        'user_id' => $request->user()->id,
                        'notes' => $item['notes'] ?? 'إدخال من سند رقم: ' . $serialNumber,
                    ]);
                }

                return [
                    'entry_note' => $entryNote->load('items'),
                    'message' => 'تم إنشاء مذكرة الإدخال بنجاح، يرجى إسناد المواقع لاحقاً'
                ];
            });

            return $this->successResponse($result['entry_note'], $result['message'], 201);

        } catch (\Exception $e) {
            return $this->handleExceptionResponse($e, 'فشل في إنشاء مذكرة الدخول');
        }
    }


    // إظهار مذكرة محددة للمستخدم الحالي
    public function show($id, Request $request)
    {
        try {
            // تحميل العلاقات المتداخلة:
            // - 'warehouse': المستودع الذي دخلت إليه المذكرة.
            // - 'user': المستخدم الذي أنشأ المذكرة.
            // - 'items.product': تفاصيل المنتج لكل عنصر في المذكرة.
            // - 'items.location': تفاصيل الموقع الذي دخل إليه المنتج لكل عنصر.
            $note = EntryNote::with([
                'warehouse',
                'user',
                'createdBy',
                'items.product',    // <--- جديد: تحميل تفاصيل المنتج لكل عنصر إدخال
            ])
                ->where('created_by', $request->user()->id) // فلترة حسب المستخدم الحالي
                ->findOrFail($id);
            return $this->successResponse($note, 'تم جلب المذكرة بنجاح');
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // استخدام notFoundResponse لرسائل 404
            return $this->notFoundResponse('المذكرة غير موجودة أو غير مصرح لك بالوصول إليها.');
        }catch (\Exception $e) {
            return $this->handleExceptionResponse($e);
        }
    }

    //لتوليد السيريال نمبر
    private function generateSerialNumber(): string
    {
        $currentYear = date('Y');

        // الحصول على آخر مذكرة لهذه السنة
        $lastEntry = EntryNote::whereYear('created_at', $currentYear)
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
