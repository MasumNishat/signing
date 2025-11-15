<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * BrandController
 *
 * Handles brand customization operations including logos, resources,
 * and email content management.
 *
 * Endpoints:
 * - GET    /brands - List brands
 * - POST   /brands - Create brand
 * - GET    /brands/{brandId} - Get brand
 * - PUT    /brands/{brandId} - Update brand
 * - DELETE /brands/{brandId} - Delete brand
 * - POST   /brands/{brandId}/logos - Upload logo
 * - GET    /brands/{brandId}/logos/{logoType} - Get logo
 * - DELETE /brands/{brandId}/logos/{logoType} - Delete logo
 * - POST   /brands/{brandId}/resources - Upload resource
 * - GET    /brands/{brandId}/resources/{resourceType} - Get resource
 * - DELETE /brands/{brandId}/resources/{resourceType} - Delete resource
 * - GET    /brands/{brandId}/email_content - Get email content
 * - PUT    /brands/{brandId}/email_content - Update email content
 */
class BrandController extends Controller
{
    /**
     * @var BrandService
     */
    protected BrandService $brandService;

    /**
     * Constructor
     */
    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    /**
     * List brands
     *
     * GET /api/v2.1/accounts/{accountId}/brands
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 20),
            ];

            $brands = $this->brandService->listBrands($account->id, $filters);

            return $this->paginatedResponse($brands, 'Brands retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create brand
     *
     * POST /api/v2.1/accounts/{accountId}/brands
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand_name' => 'required|string|max:255',
                'brand_company' => 'sometimes|string|max:255',
                'is_sending_default' => 'sometimes|boolean',
                'is_signing_default' => 'sometimes|boolean',
                'is_overriding_company_name' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $brand = $this->brandService->createBrand($account->id, $request->all());

            return $this->successResponse([
                'brand_id' => $brand->brand_id,
                'brand_name' => $brand->brand_name,
                'brand_company' => $brand->brand_company,
                'is_sending_default' => $brand->is_sending_default,
                'is_signing_default' => $brand->is_signing_default,
                'created_at' => $brand->created_at->toIso8601String(),
            ], 'Brand created successfully', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get brand
     *
     * GET /api/v2.1/accounts/{accountId}/brands/{brandId}
     *
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function show(string $accountId, string $brandId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $brand = $this->brandService->getBrand($account->id, $brandId);

            $response = [
                'brand_id' => $brand->brand_id,
                'brand_name' => $brand->brand_name,
                'brand_company' => $brand->brand_company,
                'is_sending_default' => $brand->is_sending_default,
                'is_signing_default' => $brand->is_signing_default,
                'is_overriding_company_name' => $brand->is_overriding_company_name,
                'logos' => $brand->logos->map(function ($logo) {
                    return [
                        'logo_type' => $logo->logo_type,
                        'file_name' => $logo->file_name,
                        'file_url' => $logo->getFileUrl(),
                        'file_size' => $logo->getFileSizeFormatted(),
                        'mime_type' => $logo->mime_type,
                    ];
                }),
                'resources' => $brand->resources->map(function ($resource) {
                    return [
                        'resource_type' => $resource->resource_content_type,
                        'file_name' => $resource->file_name,
                        'file_url' => $resource->getFileUrl(),
                        'mime_type' => $resource->mime_type,
                    ];
                }),
                'email_contents' => $brand->emailContents->map(function ($content) {
                    return [
                        'email_content_type' => $content->email_content_type,
                        'content' => $content->content,
                        'email_to_link' => $content->email_to_link,
                        'link_text' => $content->link_text,
                    ];
                }),
                'created_at' => $brand->created_at->toIso8601String(),
                'updated_at' => $brand->updated_at->toIso8601String(),
            ];

            return $this->successResponse($response, 'Brand retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update brand
     *
     * PUT /api/v2.1/accounts/{accountId}/brands/{brandId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $brandId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'brand_name' => 'sometimes|string|max:255',
                'brand_company' => 'sometimes|string|max:255',
                'is_sending_default' => 'sometimes|boolean',
                'is_signing_default' => 'sometimes|boolean',
                'is_overriding_company_name' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $brand = $this->brandService->updateBrand($account->id, $brandId, $request->all());

            return $this->successResponse([
                'brand_id' => $brand->brand_id,
                'brand_name' => $brand->brand_name,
                'is_sending_default' => $brand->is_sending_default,
                'is_signing_default' => $brand->is_signing_default,
            ], 'Brand updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete brand
     *
     * DELETE /api/v2.1/accounts/{accountId}/brands/{brandId}
     *
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $brandId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $this->brandService->deleteBrand($account->id, $brandId);

            return $this->noContentResponse();

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Upload brand logo
     *
     * POST /api/v2.1/accounts/{accountId}/brands/{brandId}/logos
     *
     * @param Request $request
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function uploadLogo(Request $request, string $accountId, string $brandId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'logo_type' => 'required|string|in:primary,secondary,email',
                'file' => 'required|file|mimes:jpeg,png,gif,svg|max:5120', // 5MB max
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $logo = $this->brandService->uploadLogo(
                $account->id,
                $brandId,
                $request->input('logo_type'),
                $request->file('file')
            );

            return $this->successResponse([
                'logo_type' => $logo->logo_type,
                'file_name' => $logo->file_name,
                'file_url' => $logo->getFileUrl(),
                'file_size' => $logo->getFileSizeFormatted(),
                'mime_type' => $logo->mime_type,
            ], 'Logo uploaded successfully', 201);

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get brand logo
     *
     * GET /api/v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}
     *
     * @param string $accountId
     * @param string $brandId
     * @param string $logoType
     * @return JsonResponse
     */
    public function getLogo(string $accountId, string $brandId, string $logoType): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $logo = $this->brandService->getLogo($account->id, $brandId, $logoType);

            return $this->successResponse([
                'logo_type' => $logo->logo_type,
                'file_name' => $logo->file_name,
                'file_url' => $logo->getFileUrl(),
                'file_size' => $logo->getFileSizeFormatted(),
                'mime_type' => $logo->mime_type,
            ], 'Logo retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete brand logo
     *
     * DELETE /api/v2.1/accounts/{accountId}/brands/{brandId}/logos/{logoType}
     *
     * @param string $accountId
     * @param string $brandId
     * @param string $logoType
     * @return JsonResponse
     */
    public function deleteLogo(string $accountId, string $brandId, string $logoType): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $this->brandService->deleteLogo($account->id, $brandId, $logoType);

            return $this->noContentResponse();

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Upload brand resource
     *
     * POST /api/v2.1/accounts/{accountId}/brands/{brandId}/resources
     *
     * @param Request $request
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function uploadResource(Request $request, string $accountId, string $brandId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'resource_type' => 'required|string|in:email,sending,signing,signing_captive',
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $resource = $this->brandService->uploadResource(
                $account->id,
                $brandId,
                $request->input('resource_type'),
                $request->file('file')
            );

            return $this->successResponse([
                'resource_type' => $resource->resource_content_type,
                'file_name' => $resource->file_name,
                'file_url' => $resource->getFileUrl(),
                'mime_type' => $resource->mime_type,
            ], 'Resource uploaded successfully', 201);

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get brand resource
     *
     * GET /api/v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceType}
     *
     * @param string $accountId
     * @param string $brandId
     * @param string $resourceType
     * @return JsonResponse
     */
    public function getResource(string $accountId, string $brandId, string $resourceType): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $resource = $this->brandService->getResource($account->id, $brandId, $resourceType);

            return $this->successResponse([
                'resource_type' => $resource->resource_content_type,
                'file_name' => $resource->file_name,
                'file_url' => $resource->getFileUrl(),
                'mime_type' => $resource->mime_type,
            ], 'Resource retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete brand resource
     *
     * DELETE /api/v2.1/accounts/{accountId}/brands/{brandId}/resources/{resourceType}
     *
     * @param string $accountId
     * @param string $brandId
     * @param string $resourceType
     * @return JsonResponse
     */
    public function deleteResource(string $accountId, string $brandId, string $resourceType): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $this->brandService->deleteResource($account->id, $brandId, $resourceType);

            return $this->noContentResponse();

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get brand email content
     *
     * GET /api/v2.1/accounts/{accountId}/brands/{brandId}/email_content
     *
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function getEmailContent(string $accountId, string $brandId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $emailContents = $this->brandService->getEmailContents($account->id, $brandId);

            $response = $emailContents->map(function ($content) {
                return [
                    'email_content_type' => $content->email_content_type,
                    'content' => $content->content,
                    'email_to_link' => $content->email_to_link,
                    'link_text' => $content->link_text,
                ];
            });

            return $this->successResponse($response, 'Email content retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update brand email content
     *
     * PUT /api/v2.1/accounts/{accountId}/brands/{brandId}/email_content
     *
     * @param Request $request
     * @param string $accountId
     * @param string $brandId
     * @return JsonResponse
     */
    public function updateEmailContent(Request $request, string $accountId, string $brandId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_contents' => 'required|array',
                'email_contents.*.email_content_type' => 'required|string',
                'email_contents.*.content' => 'sometimes|string',
                'email_contents.*.email_to_link' => 'sometimes|url|max:500',
                'email_contents.*.link_text' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $emailContents = $this->brandService->updateEmailContent(
                $account->id,
                $brandId,
                $request->all()
            );

            return $this->successResponse($emailContents, 'Email content updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Helper: Get account by ID
     */
    protected function getAccountById(string $accountId): \App\Models\Account
    {
        $account = \App\Models\Account::where('account_id', $accountId)->first();

        if (!$account) {
            throw new ResourceNotFoundException('Account not found');
        }

        return $account;
    }

    /**
     * Helper: Success response
     */
    protected function successResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Helper: Error response
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    /**
     * Helper: Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Helper: Validation error response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Helper: No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Helper: Paginated response
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'message' => $message,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
