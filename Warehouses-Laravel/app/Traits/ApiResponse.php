<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Validation\Validator;

trait ApiResponse
{
    /**
     * إرجاع استجابة ناجحة مع بيانات
     *
     * @param mixed $data البيانات المراد إرجاعها
     * @param string $message الرسالة التوضيحية
     * @param int $code كود الحالة HTTP
     * @return JsonResponse
     */
    public function successResponse($data = null, string $message = 'تمت العملية بنجاح', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return response()->json($response, $code);
    }

    /**
     * إرجاع استجابة ناجحة بدون بيانات
     *
     * @param string $message الرسالة التوضيحية
     * @param int $code كود الحالة HTTP
     * @return JsonResponse
     */
    public function successMessage(string $message = 'تمت العملية بنجاح', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $code);
    }

    /**
     * إرجاع استجابة خطأ
     *
     * @param string $message رسالة الخطأ
     * @param int $code كود الحالة HTTP
     * @param array $errors أخطاء تفصيلية (اختياري)
     * @param string|null $internalCode كود خطأ داخلي (اختياري)
     * @return JsonResponse
     */
    public function errorResponse(
        string $message = 'حدث خطأ ما',
        int $code = 400,
        array $errors = [],
        ?string $internalCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($internalCode) {
            $response['code'] = $internalCode;
        }

        return response()->json($response, $code);
    }

    /**
     * إرجاع أخطاء التحقق من الصحة
     *
     * @param Validator $validator
     * @param string|null $message
     * @return JsonResponse
     */
    public function validationErrorResponse(Validator $validator, ?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'بيانات غير صالحة',
            422,
            $validator->errors()->toArray(),
            $this->getValidationErrorCode($validator)
        );
    }

    /**
     * إرجاع استجابة غير مصرح بها
     *
     * @param string $message
     * @return JsonResponse
     */
    public function unauthorizedResponse(string $message = 'غير مصرح بالوصول'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * إرجاع استجابة غير موجود
     *
     * @param string $message
     * @return JsonResponse
     */
    public function notFoundResponse(string $message = 'المورد غير موجود'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * الحصول على كود الخطأ بناء على حقل التحقق
     *
     * @param Validator $validator
     * @return string
     */
    protected function getValidationErrorCode(Validator $validator): string
    {
        $errorCodes = [
            'name' => 'VALIDATION_001',
            'email' => 'VALIDATION_002',
            'password' => 'VALIDATION_003',
            // يمكن إضافة المزيد من الأكواد حسب الحاجة
        ];

        $field = $validator->errors()->keys()[0] ?? '';

        return $errorCodes[$field] ?? 'VALIDATION_000';
    }

    /**
     * تسجيل الخطأ وإرجاع استجابة
     *
     * @param \Throwable $e
     * @param string|null $customMessage
     * @return JsonResponse
     */
    public function handleExceptionResponse(\Throwable $e, ?string $customMessage = null): JsonResponse
    {
        Log::error($e->getMessage(), ['exception' => $e]);

        $message = $customMessage ?? 'حدث خطأ غير متوقع في الخادم';

        return $this->errorResponse($message, 500, [], 'SERVER_ERROR');
    }
}
