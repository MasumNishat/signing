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
     * Success response (alias for sendResponse).
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return JsonResponse
     */
    public function success($data, string $message = '', int $code = 200): JsonResponse
    {
        return $this->sendResponse($data, $message, $code);
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
     * Error response (alias for sendError).
     *
     * @param  string  $message
     * @param  array|null  $errors
     * @param  int  $code
     * @return JsonResponse
     */
    public function error(string $message, ?array $errors = null, int $code = 400): JsonResponse
    {
        return $this->sendError($message, $errors ?? [], $code);
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

    /**
     * Validation error response.
     *
     * @param  array|\Illuminate\Support\MessageBag  $errors
     * @param  string  $message
     * @return JsonResponse
     */
    public function validationError($errors, string $message = 'Validation Error'): JsonResponse
    {
        // Convert MessageBag to array if needed
        if ($errors instanceof \Illuminate\Support\MessageBag) {
            $errors = $errors->toArray();
        }

        return $this->sendValidationError($errors, $message);
    }

    /**
     * Created response (201).
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return JsonResponse
     */
    public function created($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * No content response (204).
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Not found response (404).
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, 404);
    }

    /**
     * Unauthorized response (401).
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, null, 401);
    }

    /**
     * Forbidden response (403).
     *
     * @param  string  $message
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, null, 403);
    }

    /**
     * Paginated response.
     *
     * @param  mixed  $paginator
     * @param  string  $message
     * @return JsonResponse
     */
    public function paginated($paginator, string $message = ''): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, 200);
    }
}
