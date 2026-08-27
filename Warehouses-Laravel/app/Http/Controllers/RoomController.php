<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;


class RoomController extends Controller
{

    use ApiResponse;

    public function store(Request $request)
    {
        $validator = validator($request->all(), [
            'building_id' => 'required|exists:buildings,id',
            'user_id'=>'nullable|exists:users,id',
            'room_code' => 'required|unique:rooms,room_code',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }
        DB::beginTransaction();

        try {
            $room = Room::create([
                'building_id' => $request->building_id,
                'user_id' => $request->user_id,
                'room_code' => $request->room_code,
                'description' => $request->description ?? null,
            ]);
            DB::commit();

            return $this->successResponse($room, 'تم إنشاء الغرفة بنجاح', 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->handleExceptionResponse($e);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'building_id' => 'required|exists:buildings,id',
                'room_code' => 'required|unique:rooms,room_code',
                'description' => 'nullable|string'
            ]);

            $room = Room::find($id);

            if (!$room) {
                return $this->notFoundResponse('الغرفة غير موجودة');
            }

            $room->update($validated);

            DB::commit();
            return $this->successResponse($room, 'تم تعديل الغرفة بنجاح', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Throwable $e) {
            DB::rollBack();

            return $this->handleExceptionResponse($e);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $room = Room::findOrFail($id);

            $room->delete();

            DB::commit();

            return $this->successResponse(null, 'تم حذف الغرفة بنجاح');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('الغرفة غير موجود', 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('فشل في حذف الغرفة: ' . $e->getMessage(), 500);
        }
    }

    public function index()
    {
        $rooms = Room::all();
        $count = count($rooms);
        return $this->successResponse(
            ['rooms' => $rooms,
                'count' => $count],
            'هذه هي كل الغرف يا عمي ',
            201);
    }

    public function show($id)
    {
        try {
            $room = Room::with('building:id,name')->find($id);

            if (!$room) {
                return $this->notFoundResponse('الغرفة غير موجودة');
            }

            return $this->successResponse(
                ['room' => $room],
                'تم جلب بيانات الغرفة بنجاح',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse('فشل في جلب بيانات الغرفة: ' . $e->getMessage(), 500);
        }
    }
}


