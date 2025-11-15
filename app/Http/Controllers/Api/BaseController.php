<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param  mixed  $result
     * @param  string  $message
     * @param  int  $code
     * @return JsonResponse
     */
    public function sendResponse($result, string $message = '', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $result,
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }

    /**
     * Return error response.
     *
     * @param  string  $error
     * @param  array  $errorMessages
     * @param  int  $code
     * @return JsonResponse
     */
    public function sendError(string $error, array $errorMessages = [], int $code = 404): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response.
     *
     * @param  array  $errors
     * @param  string  $message
     * @return JsonResponse
     */
    public function sendValidationError(array $errors, string $message = 'Validation Error'): JsonResponse
    {
        return $this->sendError($message, $errors, 422);
    }
}
