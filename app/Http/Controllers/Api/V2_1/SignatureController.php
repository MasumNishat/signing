<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SignatureController extends Controller
{
    protected SignatureService $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Get signature providers for an account.
     *
     * GET /v2.1/accounts/{accountId}/signatureProviders
     */
    public function getSignatureProviders(Request $request, string $accountId): JsonResponse
    {
        try {
            $providers = $this->signatureService->getSignatureProviders((int) $accountId);

            return $this->success([
                'signatureProviders' => $providers->map(function ($provider) {
                    return [
                        'providerId' => $provider->provider_id,
                        'providerName' => $provider->provider_name,
                        'priority' => $provider->priority,
                        'isRequired' => $provider->is_required,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get account signatures.
     *
     * GET /v2.1/accounts/{accountId}/signatures
     */
    public function getAccountSignatures(Request $request, string $accountId): JsonResponse
    {
        try {
            $signatures = $this->signatureService->getAccountSignatures((int) $accountId);

            return $this->success([
                'accountSignatures' => $signatures->map(function ($signature) {
                    return $this->formatSignatureResponse($signature);
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create or update account signatures.
     *
     * POST /v2.1/accounts/{accountId}/signatures
     * PUT /v2.1/accounts/{accountId}/signatures
     */
    public function createOrUpdateAccountSignatures(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'accountSignatures' => 'required|array',
            'accountSignatures.*.signature_id' => 'sometimes|string',
            'accountSignatures.*.signature_name' => 'sometimes|string|max:255',
            'accountSignatures.*.signature_type' => 'sometimes|in:signature,initials,stamp',
            'accountSignatures.*.font_style' => 'sometimes|string',
            'accountSignatures.*.status' => 'sometimes|in:active,closed',
            'accountSignatures.*.signature_image' => 'sometimes',
            'accountSignatures.*.initials_image' => 'sometimes',
            'accountSignatures.*.stamp_image' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $signatures = $this->signatureService->createOrUpdateAccountSignatures(
                (int) $accountId,
                $request->input('accountSignatures')
            );

            return $this->success([
                'accountSignatures' => $signatures->map(function ($signature) {
                    return $this->formatSignatureResponse($signature);
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific account signature.
     *
     * GET /v2.1/accounts/{accountId}/signatures/{signatureId}
     */
    public function getAccountSignature(Request $request, string $accountId, string $signatureId): JsonResponse
    {
        try {
            $signature = $this->signatureService->getSignature((int) $accountId, $signatureId);

            if (!$signature) {
                return $this->notFound('Signature not found');
            }

            return $this->success($this->formatSignatureResponse($signature));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update a specific account signature.
     *
     * PUT /v2.1/accounts/{accountId}/signatures/{signatureId}
     */
    public function updateAccountSignature(Request $request, string $accountId, string $signatureId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signature_name' => 'sometimes|string|max:255',
            'signature_type' => 'sometimes|in:signature,initials,stamp',
            'font_style' => 'sometimes|string',
            'status' => 'sometimes|in:active,closed',
            'signature_image' => 'sometimes',
            'initials_image' => 'sometimes',
            'stamp_image' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $signature = $this->signatureService->updateSignature(
                (int) $accountId,
                $signatureId,
                $request->all()
            );

            return $this->success($this->formatSignatureResponse($signature));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Close (delete) an account signature.
     *
     * DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}
     */
    public function deleteAccountSignature(Request $request, string $accountId, string $signatureId): JsonResponse
    {
        try {
            $result = $this->signatureService->closeSignature((int) $accountId, $signatureId);

            if (!$result) {
                return $this->notFound('Signature not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a signature image.
     *
     * GET /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}
     */
    public function getAccountSignatureImage(Request $request, string $accountId, string $signatureId, string $imageType): JsonResponse
    {
        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $image = $this->signatureService->getSignatureImage(
                (int) $accountId,
                $signatureId,
                $imageTypeMap[$imageType]
            );

            if (!$image || !$image->fileExists()) {
                return $this->notFound('Image not found');
            }

            // Return the actual image file
            return response()->file(storage_path('app/private/' . $image->file_path), [
                'Content-Type' => $image->mime_type,
                'Content-Disposition' => 'inline; filename="' . $image->file_name . '"',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Upload a signature image.
     *
     * PUT /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}
     */
    public function uploadAccountSignatureImage(Request $request, string $accountId, string $signatureId, string $imageType): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'include_chrome' => 'sometimes|boolean',
            'transparent_png' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $signature = $this->signatureService->getSignature((int) $accountId, $signatureId);

            if (!$signature) {
                return $this->notFound('Signature not found');
            }

            $imageData = $request->file('image') ?? $request->input('image');

            $image = $this->signatureService->uploadSignatureImage(
                $signature,
                $imageTypeMap[$imageType],
                $imageData,
                $request->only(['include_chrome', 'transparent_png'])
            );

            return $this->success([
                'imageType' => $imageType,
                'filePath' => $image->file_path,
                'fileName' => $image->file_name,
                'mimeType' => $image->mime_type,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a signature image.
     *
     * DELETE /v2.1/accounts/{accountId}/signatures/{signatureId}/{imageType}
     */
    public function deleteAccountSignatureImage(Request $request, string $accountId, string $signatureId, string $imageType): JsonResponse
    {
        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $result = $this->signatureService->deleteSignatureImage(
                (int) $accountId,
                $signatureId,
                $imageTypeMap[$imageType]
            );

            if (!$result) {
                return $this->notFound('Image not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get user signatures.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/signatures
     */
    public function getUserSignatures(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $signatures = $this->signatureService->getUserSignatures((int) $accountId, (int) $userId);

            return $this->success([
                'userSignatures' => $signatures->map(function ($signature) {
                    return $this->formatSignatureResponse($signature);
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create or update user signatures.
     *
     * POST /v2.1/accounts/{accountId}/users/{userId}/signatures
     * PUT /v2.1/accounts/{accountId}/users/{userId}/signatures
     */
    public function createOrUpdateUserSignatures(Request $request, string $accountId, string $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'userSignatures' => 'required|array',
            'userSignatures.*.signature_id' => 'sometimes|string',
            'userSignatures.*.signature_name' => 'sometimes|string|max:255',
            'userSignatures.*.signature_type' => 'sometimes|in:signature,initials,stamp',
            'userSignatures.*.font_style' => 'sometimes|string',
            'userSignatures.*.status' => 'sometimes|in:active,closed',
            'userSignatures.*.signature_image' => 'sometimes',
            'userSignatures.*.initials_image' => 'sometimes',
            'userSignatures.*.stamp_image' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $signatures = $this->signatureService->createOrUpdateUserSignatures(
                (int) $accountId,
                (int) $userId,
                $request->input('userSignatures')
            );

            return $this->success([
                'userSignatures' => $signatures->map(function ($signature) {
                    return $this->formatSignatureResponse($signature);
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific user signature.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}
     */
    public function getUserSignature(Request $request, string $accountId, string $userId, string $signatureId): JsonResponse
    {
        try {
            $signature = $this->signatureService->getSignature((int) $accountId, $signatureId, (int) $userId);

            if (!$signature) {
                return $this->notFound('Signature not found');
            }

            return $this->success($this->formatSignatureResponse($signature));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update a specific user signature.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}
     */
    public function updateUserSignature(Request $request, string $accountId, string $userId, string $signatureId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signature_name' => 'sometimes|string|max:255',
            'signature_type' => 'sometimes|in:signature,initials,stamp',
            'font_style' => 'sometimes|string',
            'status' => 'sometimes|in:active,closed',
            'signature_image' => 'sometimes',
            'initials_image' => 'sometimes',
            'stamp_image' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $signature = $this->signatureService->updateSignature(
                (int) $accountId,
                $signatureId,
                $request->all(),
                (int) $userId
            );

            return $this->success($this->formatSignatureResponse($signature));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a user signature.
     *
     * DELETE /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}
     */
    public function deleteUserSignature(Request $request, string $accountId, string $userId, string $signatureId): JsonResponse
    {
        try {
            $result = $this->signatureService->closeSignature((int) $accountId, $signatureId, (int) $userId);

            if (!$result) {
                return $this->notFound('Signature not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a user signature image.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}
     */
    public function getUserSignatureImage(Request $request, string $accountId, string $userId, string $signatureId, string $imageType)
    {
        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $image = $this->signatureService->getSignatureImage(
                (int) $accountId,
                $signatureId,
                $imageTypeMap[$imageType],
                (int) $userId
            );

            if (!$image || !$image->fileExists()) {
                return $this->notFound('Image not found');
            }

            // Return the actual image file
            return response()->file(storage_path('app/private/' . $image->file_path), [
                'Content-Type' => $image->mime_type,
                'Content-Disposition' => 'inline; filename="' . $image->file_name . '"',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Upload a user signature image.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}
     */
    public function uploadUserSignatureImage(Request $request, string $accountId, string $userId, string $signatureId, string $imageType): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'include_chrome' => 'sometimes|boolean',
            'transparent_png' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $signature = $this->signatureService->getSignature((int) $accountId, $signatureId, (int) $userId);

            if (!$signature) {
                return $this->notFound('Signature not found');
            }

            $imageData = $request->file('image') ?? $request->input('image');

            $image = $this->signatureService->uploadSignatureImage(
                $signature,
                $imageTypeMap[$imageType],
                $imageData,
                $request->only(['include_chrome', 'transparent_png'])
            );

            return $this->success([
                'imageType' => $imageType,
                'filePath' => $image->file_path,
                'fileName' => $image->file_name,
                'mimeType' => $image->mime_type,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a user signature image.
     *
     * DELETE /v2.1/accounts/{accountId}/users/{userId}/signatures/{signatureId}/{imageType}
     */
    public function deleteUserSignatureImage(Request $request, string $accountId, string $userId, string $signatureId, string $imageType): JsonResponse
    {
        try {
            $imageTypeMap = [
                'signature_image' => 'signature_image',
                'initials_image' => 'initials_image',
                'stamp_image' => 'stamp_image',
            ];

            if (!isset($imageTypeMap[$imageType])) {
                return $this->error('Invalid image type', 400);
            }

            $result = $this->signatureService->deleteSignatureImage(
                (int) $accountId,
                $signatureId,
                $imageTypeMap[$imageType],
                (int) $userId
            );

            if (!$result) {
                return $this->notFound('Image not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get account seals.
     *
     * GET /v2.1/accounts/{accountId}/seals
     */
    public function getSeals(Request $request, string $accountId): JsonResponse
    {
        try {
            $seals = $this->signatureService->getSeals((int) $accountId);

            return $this->success([
                'seals' => $seals->map(function ($seal) {
                    return [
                        'sealId' => $seal->seal_id,
                        'sealName' => $seal->seal_name,
                        'sealIdentifier' => $seal->seal_identifier,
                        'status' => $seal->status,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific seal.
     *
     * GET /v2.1/accounts/{accountId}/seals/{sealId}
     */
    public function getSeal(string $accountId, string $sealId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $seal = $this->signatureService->getSeal($account->id, $sealId);

            if (!$seal) {
                return $this->notFound('Seal not found');
            }

            return $this->success([
                'sealId' => $seal->seal_id,
                'sealName' => $seal->seal_name,
                'sealIdentifier' => $seal->seal_identifier,
                'status' => $seal->status,
                'createdAt' => $seal->created_at?->toIso8601String(),
                'updatedAt' => $seal->updated_at?->toIso8601String(),
            ], 'Seal retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create a new seal.
     *
     * POST /v2.1/accounts/{accountId}/seals
     */
    public function createSeal(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'seal_name' => 'required|string|max:255',
            'seal_identifier' => 'required|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $seal = $this->signatureService->createSeal($account->id, $request->all());

            return $this->created([
                'sealId' => $seal->seal_id,
                'sealName' => $seal->seal_name,
                'sealIdentifier' => $seal->seal_identifier,
                'status' => $seal->status,
                'createdAt' => $seal->created_at?->toIso8601String(),
            ], 'Seal created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update an existing seal.
     *
     * PUT /v2.1/accounts/{accountId}/seals/{sealId}
     */
    public function updateSeal(Request $request, string $accountId, string $sealId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'seal_name' => 'sometimes|string|max:255',
            'seal_identifier' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $seal = $this->signatureService->updateSeal($account->id, $sealId, $request->all());

            if (!$seal) {
                return $this->notFound('Seal not found');
            }

            return $this->success([
                'sealId' => $seal->seal_id,
                'sealName' => $seal->seal_name,
                'sealIdentifier' => $seal->seal_identifier,
                'status' => $seal->status,
                'updatedAt' => $seal->updated_at?->toIso8601String(),
            ], 'Seal updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a seal.
     *
     * DELETE /v2.1/accounts/{accountId}/seals/{sealId}
     */
    public function deleteSeal(string $accountId, string $sealId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $deleted = $this->signatureService->deleteSeal($account->id, $sealId);

            if (!$deleted) {
                return $this->notFound('Seal not found');
            }

            return $this->noContent('Seal deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Format signature response.
     */
    private function formatSignatureResponse($signature): array
    {
        $data = [
            'signatureId' => $signature->signature_id,
            'signatureType' => $signature->signature_type,
            'signatureName' => $signature->signature_name,
            'status' => $signature->status,
            'fontStyle' => $signature->font_style,
            'adoptedDateTime' => $signature->adopted_date_time?->toIso8601String(),
            'createdDateTime' => $signature->created_date_time?->toIso8601String(),
        ];

        // Include images if loaded
        if ($signature->relationLoaded('images')) {
            foreach ($signature->images as $image) {
                $imageKey = match ($image->image_type) {
                    'signature_image' => 'signatureImageUri',
                    'initials_image' => 'initialsImageUri',
                    'stamp_image' => 'stampImageUri',
                    default => null,
                };

                if ($imageKey) {
                    $data[$imageKey] = '/api/v2.1/accounts/' . $signature->account_id .
                        ($signature->user_id ? '/users/' . $signature->user_id : '') .
                        '/signatures/' . $signature->signature_id . '/' . $image->image_type;
                }
            }
        }

        return $data;
    }
}
