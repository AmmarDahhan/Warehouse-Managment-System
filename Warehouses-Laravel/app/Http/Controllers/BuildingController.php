<?php

namespace App\Http\Controllers;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponse;

class BuildingController extends Controller
{
    use ApiResponse;
    public function store(Request $request)
    {
        $validator=validator($request->all(),[
            'name'=>'required|string',
            'location'=>'required|string',
            'notes'=>'nullable|string'
        ]);

        if($validator->fails()){
            return $this->validationErrorResponse($validator);
        }
        DB::beginTransaction();

        try {
            $building = Building::create([
                'name' => $request->name,
                'location' => $request->location,
                'notes' => $request->notes ?? null,
            ]);
            DB::commit();

            return $this->successResponse($building,'تم إنشاء المبنى بنجاح',201);
        }catch (\Throwable $e){
            DB::rollBack();
            return $this->handleExceptionResponse($e);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name' => 'string|max:255',
                'location' => 'string|max:255',
                'notes'=>'nullable|string'
            ]);

            $building = Building::find($id);

            if (!$building) {
                return $this->notFoundResponse('المبنى غير موجودة');
            }

            $building->update($validated);

            DB::commit();
            return $this->successResponse($building, 'تم تعديل المبنى بنجاح',201);
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
            $building = Building::findOrFail($id);

            $building->delete();

            DB::commit();

            return $this->successResponse(null, 'تم حذف المبنى بنجاح');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse('المبنى غير موجود', 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('فشل في حذف المبنى: ' . $e->getMessage(), 500);
        }
    }

    public function index()
    {
        $buildings=Building::all();
        $count=count($buildings);
        return $this->successResponse(
            ['buildings'=>$buildings,
                'count'=>$count],
            'هذه هي كل المبنيات يا عمي ',
            201);
    }

    public function show($id)
    {
        try {
            $building = Building::find($id);

            if (!$building) {
                return $this->notFoundResponse('المبنى غير موجود');
            }

            return $this->successResponse(
                ['building' => $building],
                'تم جلب بيانات المبنى بنجاح',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse('فشل في جلب بيانات المبنى: ' . $e->getMessage(), 500);
        }
    }
}
