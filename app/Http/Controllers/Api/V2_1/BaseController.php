<?php

namespace App\Http\Controllers\Api\V2_1;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Default pagination limit.
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum pagination limit.
     */
    protected int $maxPerPage = 100;

    /**
     * Return a success response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $code = 200
    ): JsonResponse {
        $response = [
            'success' => true,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($message !== null) {
            $response['message'] = $message;
        }

        $response['meta'] = $this->getMetadata();

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @param string|null $errorCode
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $code = 400,
        mixed $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
            ],
        ];

        if ($errorCode !== null) {
            $response['error']['code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['error']['details'] = $errors;
        }

        $response['meta'] = $this->getMetadata();

        return response()->json($response, $code);
    }

    /**
     * Return a paginated response.
     *
     * @param LengthAwarePaginator $paginator
     * @param string|null $message
     * @return JsonResponse
     */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        ?string $message = null
    ): JsonResponse {
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
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        $response['meta'] = $this->getMetadata();

        return response()->json($response);
    }

    /**
     * Return a created response (201).
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    protected function createdResponse(
        mixed $data = null,
        ?string $message = null
    ): JsonResponse {
        return $this->successResponse($data, $message ?? 'Resource created successfully', 201);
    }

    /**
     * Return a no content response (204).
     *
     * @return JsonResponse
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response (404).
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Resource not found',
            404,
            null,
            'RESOURCE_NOT_FOUND'
        );
    }

    /**
     * Return an unauthorized response (401).
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Unauthorized',
            401,
            null,
            'UNAUTHORIZED'
        );
    }

    /**
     * Return a forbidden response (403).
     *
     * @param string|null $message
     * @return JsonResponse
     */
    protected function forbiddenResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse(
            $message ?? 'Forbidden',
            403,
            null,
            'FORBIDDEN'
        );
    }

    /**
     * Return a validation error response (422).
     *
     * @param mixed $errors
     * @param string|null $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        mixed $errors,
        ?string $message = null
    ): JsonResponse {
        return $this->errorResponse(
            $message ?? 'Validation failed',
            422,
            $errors,
            'VALIDATION_ERROR'
        );
    }

    /**
     * Get metadata for the response.
     *
     * @return array
     */
    protected function getMetadata(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID') ?? Str::uuid()->toString(),
            'version' => 'v2.1',
        ];
    }

    /**
     * Get pagination parameters from request.
     *
     * @return array
     */
    protected function getPaginationParams(): array
    {
        $perPage = (int) request()->query('per_page', $this->defaultPerPage);
        $perPage = min($perPage, $this->maxPerPage);
        $perPage = max($perPage, 1);

        return [
            'per_page' => $perPage,
            'page' => (int) request()->query('page', 1),
        ];
    }

    /**
     * Apply sorting to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $allowedFields
     * @param string $defaultField
     * @param string $defaultDirection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySort(
        $query,
        array $allowedFields,
        string $defaultField = 'created_at',
        string $defaultDirection = 'desc'
    ) {
        $sortBy = request()->query('sort_by', $defaultField);
        $sortDirection = request()->query('sort_direction', $defaultDirection);

        // Validate sort field
        if (!in_array($sortBy, $allowedFields)) {
            $sortBy = $defaultField;
        }

        // Validate sort direction
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = $defaultDirection;
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Apply filters to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $config) {
            $value = request()->query($field);

            if ($value === null) {
                continue;
            }

            $operator = $config['operator'] ?? '=';
            $column = $config['column'] ?? $field;

            match ($operator) {
                '=' => $query->where($column, $value),
                'like' => $query->where($column, 'like', "%{$value}%"),
                'in' => $query->whereIn($column, is_array($value) ? $value : explode(',', $value)),
                '>' => $query->where($column, '>', $value),
                '>=' => $query->where($column, '>=', $value),
                '<' => $query->where($column, '<', $value),
                '<=' => $query->where($column, '<=', $value),
                '!=' => $query->where($column, '!=', $value),
                'between' => is_array($value) && count($value) === 2
                    ? $query->whereBetween($column, $value)
                    : null,
                'null' => $value === 'true' || $value === '1'
                    ? $query->whereNull($column)
                    : $query->whereNotNull($column),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Apply search to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $searchableFields
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applySearch($query, array $searchableFields)
    {
        $search = request()->query('search');

        if (!$search || empty($searchableFields)) {
            return $query;
        }

        return $query->where(function ($query) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $query->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply date range filter to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column
     * @param string|null $startParam
     * @param string|null $endParam
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyDateRange(
        $query,
        string $column = 'created_at',
        ?string $startParam = 'start_date',
        ?string $endParam = 'end_date'
    ) {
        $startDate = request()->query($startParam);
        $endDate = request()->query($endParam);

        if ($startDate) {
            $query->where($column, '>=', $startDate);
        }

        if ($endDate) {
            $query->where($column, '<=', $endDate);
        }

        return $query;
    }
}
