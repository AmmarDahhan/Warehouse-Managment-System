<?php

namespace App\Http\Controllers;

use App\Models\Custody;
use App\Models\CustodyItem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CustodyController extends Controller
{
    use ApiResponse;

    // يدوي
    public function store(Request $request)
    {
        $validator= validator($request->all(),[
            'user_id'=>'required|exists:users,id',
            //'room_id'=>'nullable|exists:rooms,id',
            'date'=>'required|date',
            'notes'=>'nullable|string',
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|exists:products,id',
            'items.*.exit_note_id'=>'required|exists:exit_notes,id',
            'items.*.quantity'=>'required|numeric|min:0.01',
            'items.*.notes'=>'nullable|string',
        ]);

        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }
        DB::beginTransaction();
        try{
            $custody= Custody::create([
                'user_id'=>$request->user_id,
                //'room_id'=>$request->room_id,
                'date'=>$request->date,
                'notes'=>$request->notes
            ]);
            foreach ($request->items as $item){
                CustodyItem::create([
                    'custody_id'=>$custody->id,
                    'product_id'=>$item['product_id'],
                    'exit_note_id'=>$item['exit_note_id'],
                    'quantity'=>$item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
            DB::commit();

            return $this->successResponse(
                $custody->load('items'),
                'تم إنشاء العهدة بنجاح',
                201
            );
        }catch (\Throwable $e){
            DB::rollBack();
            // Return the actual error message and file location
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    // عرض كل عهد شخص ما
    public function showAllForUser(Request $request)
    {
       $user=$request->user();
       $custodies= Custody::with('items.product','room')
           ->where('user_id',$user->id)
           ->get();

        if ($custodies->isEmpty()) {
            return $this->successResponse(null, 'لا يوجد لديك أي عهد حالياً.');
        }

        return $this->successResponse($custodies, 'تم استرداد العهد بنجاح.');
    }

    // عرض عهدة محددة
    public function showSpecific(Request $request, $custodyId)
    {
        $user = $request->user();

        try {
            // تم تعديل الاستعلام هنا
            $custody = Custody::with([
                // تحميل العناصر ومنتجاتها (كما كان في السابق)
                'items.product',

                // جديد: تحميل تفاصيل الإرجاع لكل عنصر
                // سيتم تحميل تفاصيل طلب الإرجاع الرئيسي (custodyReturn) والمستودع لكل عنصر مُرجع
                'items.returnItems.custodyReturn',
                'items.returnItems.warehouse'
            ])
                ->where('id', $custodyId)
                //->where('user_id', $user->id) // يمكنك تفعيل هذا السطر للتحقق من الصلاحية
                ->first(); // استخدم first() بدلاً من get() لأنك تتوقع نتيجة واحدة

            if (!$custody) {
                return $this->notFoundResponse('العهدة غير موجودة أو لا تملك صلاحية عرضها.');
            }

            return $this->successResponse($custody, 'تم استرداد العهدة بنجاح.');

        } catch (\Throwable $e) {
            // يمكنك إضافة تسجيل للخطأ هنا لمساعدتك في المستقبل
            // Log::error($e->getMessage());
            return $this->errorResponse('حدث خطأ أثناء استرداد العهدة.', 500);
        }
    }
    // نجيب كل العهد الموجودة في جدول العهد يعني لكل المستخدمين
    public function showAll(Request $request)
    {
        /**
        $user = $request->user();

        // **فحص الصلاحيات باستخدام الترايت**
        // استخدام UnauthorizedResponse من الترايت لإرجاع استجابة 403 بشكل موحد
        // (يفترض أن لديك طريقة لتعريف الصلاحيات، هنا استخدمنا isAdmin كمثال)
        if (!isset($user->isAdmin) || !$user->isAdmin) {
            return $this->unauthorizedResponse('ليس لديك صلاحية لعرض جميع العهد.');
        }

         * */

        $allCustodies = Custody::with('user', 'items.product','room')->get();

        // يمكنك أيضاً إضافة فحص هنا إذا كانت لا توجد أي عهدة على الإطلاق
        if ($allCustodies->isEmpty()) {
            return $this->successResponse(null, 'لا توجد أي عهد مسجلة حالياً.');
        }

        return $this->successResponse($allCustodies, 'تم استرداد جميع العهد بنجاح.');
    }

    // عرض عهد غرفة ما
    public function showRoomCustodies(Request $request, int $roomId) // هنا التغيير
    {

        try {
            // **البحث اليدوي عن الغرفة:**
            $room = Room::find($roomId); // البحث عن الغرفة بالـ ID

            if (!$room) {
                // إذا لم يتم العثور على الغرفة، أرجع استجابة خطأ مخصصة
                return $this->notFoundResponse('لا توجد غرفة بهذا المعرف (ID).', 404);
            }

            // بقية الكود يبقى كما هو تقريباً، لكن الآن تستخدم $room بدلاً من $roomId مباشرةً
            $custodyItems = CustodyItem::with([
                'product',
                'custody.user',
                'exitNote',
                'room'
            ])
                ->where('room_id', $room->id) // استخدام $room->id
                ->get();

            if ($custodyItems->isEmpty()) {
                return $this->successResponse(null, 'لا توجد أي عهد مسجلة لهذه الغرفة حالياً.', 200);
            }

            return $this->successResponse([
                'room' => $room->load('building'),
                'custody_items' => $custodyItems
            ], 'تم استرداد عهد الغرفة بنجاح.', 200);

        } catch (\Throwable $e) {
            return $this->errorResponse(
                message: 'فشل في استرداد عهد الغرفة: ' . $e->getMessage(),
                code: 500,
                internalCode: 'ROOM_CUSTODY_RETRIEVAL_FAILED'
            );
        }
    }

    public function getSpecificUserRooms(Request $request, User $user)
    {
        $warehouseManager = $request->user();
        // جلب الغرف التي يمتلكها المستخدم المحدد (user_id في جدول rooms)
        // وتحميل علاقة المبنى لكل غرفة.
        $rooms = $user->ownedRooms()->with('building')->get(); // **مهم:** تأكد أن `ownedRooms` معرفة في نموذج `User`

        if ($rooms->isEmpty()) {
            return $this->successResponse(null, 'هذا المستخدم ليس لديه أي غرف مسجلة باسمه حالياً.', 200);
        }

        return $this->successResponse($rooms, 'تم استرداد غرف المستخدم بنجاح.', 200);
    }

    public function assignRoomsToCustodyItems(Request $request)
    {
        //$warehouseManager = $request->user();

        // **التحقق من صلاحية أمين المستودع:**
//        if (!$warehouseManager || !property_exists($warehouseManager, 'is_warehouse_manager') || !$warehouseManager->is_warehouse_manager) {
//            return $this->unauthorizedResponse('ليس لديك صلاحية لتعيين الغرف للعهد.');
//        }

        // **Validator للبيانات المستلمة:**
        $validator = Validator::make($request->all(), [
            'requester_id' => 'required|exists:users,id', // ID للمستخدم الذي طلب المواد (صاحب العهدة)
            'assignments' => 'required|array|min:1', // مصفوفة من التعيينات
            'assignments.*.custody_item_id' => 'required|exists:custody_items,id', // ID لعنصر العهدة
            'assignments.*.room_id' => [ // ID للغرفة (يمكن أن يكون null لإزالة التعيين أو تركه غير معين)
                'nullable', // يمكن أن يكون null
                'exists:rooms,id', // يجب أن تكون الغرفة موجودة في جدول الغرف إذا لم تكن null
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        // جلب المستخدم الذي طلب المواد (صاحب العهدة)
        $requester = User::find($request->requester_id);
        if (!$requester) {
            return $this->notFoundResponse('المستخدم الذي طلب المواد غير موجود.');
        }

        // جلب جميع الغرف التي يمتلكها الـ requester لتحقيق الأمن والتحقق
        // **ملاحظة:** تأكد أن `ownedRooms` معرفة في نموذج `User`
        $requesterOwnedRoomIds = $requester->ownedRooms->pluck('id')->toArray();

        DB::beginTransaction();
        try {
            $updatedCustodyItems = collect();

            foreach ($request->assignments as $assignment) {
                $custodyItem = CustodyItem::with('custody')->findOrFail($assignment['custody_item_id']);

                // **التحقق من أن عنصر العهدة هذا ينتمي بالفعل إلى العهدة الصحيحة (صاحب العهدة)**
                // هذا يمنع أمين المستودع من تعيين غرفة لعهدة ليست تابعة للمستخدم المستهدف
                if ($custodyItem->custody->user_id !== $requester->id) {
                    throw new \Exception("عنصر العهدة ID {$custodyItem->id} لا ينتمي إلى المستخدم المطلوب ID {$requester->id}.");
                }

                $roomId = $assignment['room_id'];

                // **التحقق من أن الغرفة إذا تم تحديدها، فهي من الغرف التي يمتلكها الـ requester**
                if ($roomId !== null && !in_array($roomId, $requesterOwnedRoomIds)) {
                    throw new \Exception("الغرفة ID {$roomId} لا تنتمي إلى المستخدم الذي طلب المواد ({$requester->name}).");
                }

                // تحديث room_id لعنصر العهدة
                $custodyItem->update(['room_id' => $roomId]);

                // تحميل العلاقات المطلوبة لعنصر العهدة المحدث لإرجاعه في الاستجابة
                $updatedCustodyItems->push($custodyItem->load('product', 'room.building'));
            }

            DB::commit(); // تأكيد جميع التغييرات

            return $this->successResponse(
                $updatedCustodyItems,
                'تم تعيين الغرف لعناصر العهدة بنجاح.'
            );

        } catch (\Throwable $e) {
            DB::rollBack(); // التراجع عن جميع التغييرات في حالة حدوث خطأ
            return $this->errorResponse(
                message: 'فشل في تعيين الغرف لعناصر العهدة: ' . $e->getMessage(),
                code: 422, // Unprocessable Entity للإشارة إلى خطأ منطقي في البيانات المدخلة
                internalCode: 'ASSIGN_ROOMS_BULK_FAILED'
            );
        }
    }
}
