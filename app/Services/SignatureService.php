<?php

namespace App\Services;

use App\Models\Signature;
use App\Models\SignatureImage;
use App\Models\SignatureProvider;
use App\Models\Seal;
use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class SignatureService
{
    /**
     * Get all signature providers for an account.
     */
    public function getSignatureProviders(int $accountId): Collection
    {
        return SignatureProvider::forAccount($accountId)
            ->orderedByPriority()
            ->get();
    }

    /**
     * Get all signatures for an account.
     */
    public function getAccountSignatures(int $accountId): Collection
    {
        return Signature::with(['images'])
            ->forAccount($accountId)
            ->whereNull('user_id')
            ->active()
            ->get();
    }

    /**
     * Get all signatures for a user.
     */
    public function getUserSignatures(int $accountId, int $userId): Collection
    {
        return Signature::with(['images'])
            ->forAccount($accountId)
            ->forUser($userId)
            ->active()
            ->get();
    }

    /**
     * Get a specific signature.
     */
    public function getSignature(int $accountId, string $signatureId, ?int $userId = null): ?Signature
    {
        $query = Signature::with(['images'])
            ->forAccount($accountId)
            ->where('signature_id', $signatureId);

        if ($userId) {
            $query->forUser($userId);
        } else {
            $query->whereNull('user_id');
        }

        return $query->first();
    }

    /**
     * Create or update account-level signatures.
     */
    public function createOrUpdateAccountSignatures(int $accountId, array $signaturesData): Collection
    {
        $signatures = collect();

        DB::beginTransaction();
        try {
            foreach ($signaturesData as $signatureData) {
                if (isset($signatureData['signature_id'])) {
                    // Update existing signature
                    $signature = $this->updateSignature($accountId, $signatureData['signature_id'], $signatureData);
                } else {
                    // Create new signature
                    $signature = $this->createSignature($accountId, null, $signatureData);
                }

                if ($signature) {
                    $signatures->push($signature->load('images'));
                }
            }

            DB::commit();
            return $signatures;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create or update user-level signatures.
     */
    public function createOrUpdateUserSignatures(int $accountId, int $userId, array $signaturesData): Collection
    {
        $signatures = collect();

        DB::beginTransaction();
        try {
            foreach ($signaturesData as $signatureData) {
                if (isset($signatureData['signature_id'])) {
                    // Update existing signature
                    $signature = $this->updateSignature($accountId, $signatureData['signature_id'], $signatureData, $userId);
                } else {
                    // Create new signature
                    $signature = $this->createSignature($accountId, $userId, $signatureData);
                }

                if ($signature) {
                    $signatures->push($signature->load('images'));
                }
            }

            DB::commit();
            return $signatures;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a new signature.
     */
    public function createSignature(int $accountId, ?int $userId, array $data): Signature
    {
        $signatureData = [
            'account_id' => $accountId,
            'user_id' => $userId,
            'signature_type' => $data['signature_type'] ?? Signature::TYPE_SIGNATURE,
            'signature_name' => $data['signature_name'] ?? null,
            'status' => $data['status'] ?? Signature::STATUS_ACTIVE,
            'font_style' => $data['font_style'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'stamp_type' => $data['stamp_type'] ?? null,
            'stamp_size_mm' => $data['stamp_size_mm'] ?? null,
        ];

        if (isset($data['adopted_date_time'])) {
            $signatureData['adopted_date_time'] = $data['adopted_date_time'];
        }

        $signature = Signature::create($signatureData);

        // Handle image uploads if provided
        if (isset($data['signature_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_SIGNATURE, $data['signature_image'], $data);
        }

        if (isset($data['initials_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_INITIALS, $data['initials_image'], $data);
        }

        if (isset($data['stamp_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_STAMP, $data['stamp_image'], $data);
        }

        return $signature;
    }

    /**
     * Update an existing signature.
     */
    public function updateSignature(int $accountId, string $signatureId, array $data, ?int $userId = null): Signature
    {
        $signature = $this->getSignature($accountId, $signatureId, $userId);

        if (!$signature) {
            throw new \Exception("Signature not found");
        }

        $updateData = [];

        if (isset($data['signature_name'])) {
            $updateData['signature_name'] = $data['signature_name'];
        }

        if (isset($data['signature_type'])) {
            $updateData['signature_type'] = $data['signature_type'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['font_style'])) {
            $updateData['font_style'] = $data['font_style'];
        }

        if (isset($data['phone_number'])) {
            $updateData['phone_number'] = $data['phone_number'];
        }

        if (isset($data['stamp_type'])) {
            $updateData['stamp_type'] = $data['stamp_type'];
        }

        if (isset($data['stamp_size_mm'])) {
            $updateData['stamp_size_mm'] = $data['stamp_size_mm'];
        }

        if (isset($data['adopted_date_time'])) {
            $updateData['adopted_date_time'] = $data['adopted_date_time'];
        }

        $signature->update($updateData);

        // Handle image uploads if provided
        if (isset($data['signature_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_SIGNATURE, $data['signature_image'], $data);
        }

        if (isset($data['initials_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_INITIALS, $data['initials_image'], $data);
        }

        if (isset($data['stamp_image'])) {
            $this->uploadSignatureImage($signature, SignatureImage::TYPE_STAMP, $data['stamp_image'], $data);
        }

        return $signature->fresh(['images']);
    }

    /**
     * Close (soft delete) a signature.
     */
    public function closeSignature(int $accountId, string $signatureId, ?int $userId = null): bool
    {
        $signature = $this->getSignature($accountId, $signatureId, $userId);

        if (!$signature) {
            return false;
        }

        $signature->close();
        return true;
    }

    /**
     * Get a signature image.
     */
    public function getSignatureImage(int $accountId, string $signatureId, string $imageType, ?int $userId = null): ?SignatureImage
    {
        $signature = $this->getSignature($accountId, $signatureId, $userId);

        if (!$signature) {
            return null;
        }

        return $signature->images()->where('image_type', $imageType)->first();
    }

    /**
     * Upload or update a signature image.
     */
    public function uploadSignatureImage(Signature $signature, string $imageType, $imageData, array $options = []): SignatureImage
    {
        // Delete existing image of this type
        $existingImage = $signature->images()->where('image_type', $imageType)->first();
        if ($existingImage) {
            $existingImage->delete();
        }

        // Handle file upload
        $filePath = null;
        $fileName = null;
        $mimeType = null;

        if ($imageData instanceof UploadedFile) {
            // Real file upload
            $directory = "signatures/{$signature->account_id}/" . ($signature->user_id ?? 'account');
            $fileName = $imageData->getClientOriginalName();
            $filePath = $imageData->store($directory, 'private');
            $mimeType = $imageData->getMimeType();
        } elseif (is_string($imageData) && str_starts_with($imageData, 'data:')) {
            // Base64 encoded image
            [$mimeType, $base64Data] = $this->parseBase64Image($imageData);
            $directory = "signatures/{$signature->account_id}/" . ($signature->user_id ?? 'account');
            $extension = $this->getMimeExtension($mimeType);
            $fileName = $imageType . '_' . time() . '.' . $extension;
            $filePath = $directory . '/' . $fileName;
            Storage::disk('private')->put($filePath, base64_decode($base64Data));
        } else {
            throw new \Exception("Invalid image data format");
        }

        // Create signature image record
        return SignatureImage::create([
            'signature_id' => $signature->id,
            'image_type' => $imageType,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'include_chrome' => $options['include_chrome'] ?? false,
            'transparent_png' => $options['transparent_png'] ?? false,
        ]);
    }

    /**
     * Delete a signature image.
     */
    public function deleteSignatureImage(int $accountId, string $signatureId, string $imageType, ?int $userId = null): bool
    {
        $image = $this->getSignatureImage($accountId, $signatureId, $imageType, $userId);

        if (!$image) {
            return false;
        }

        return $image->delete();
    }

    /**
     * Get all seals for an account.
     */
    public function getSeals(int $accountId): Collection
    {
        return Seal::forAccount($accountId)
            ->active()
            ->get();
    }

    /**
     * Parse base64 image data.
     */
    private function parseBase64Image(string $base64String): array
    {
        if (!preg_match('/^data:([^;]+);base64,(.+)$/', $base64String, $matches)) {
            throw new \Exception("Invalid base64 image format");
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * Get file extension from MIME type.
     */
    private function getMimeExtension(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
        ];

        return $extensions[$mimeType] ?? 'bin';
    }

    /**
     * Get specific seal.
     */
    public function getSeal(int $accountId, string $sealId): ?Seal
    {
        return Seal::where('account_id', $accountId)
            ->where('seal_id', $sealId)
            ->first();
    }

    /**
     * Create a new seal.
     */
    public function createSeal(int $accountId, array $data): Seal
    {
        $seal = Seal::create([
            'account_id' => $accountId,
            'seal_name' => $data['seal_name'] ?? null,
            'seal_identifier' => $data['seal_identifier'] ?? null,
            'status' => $data['status'] ?? Seal::STATUS_ACTIVE,
        ]);

        return $seal;
    }

    /**
     * Update an existing seal.
     */
    public function updateSeal(int $accountId, string $sealId, array $data): ?Seal
    {
        $seal = $this->getSeal($accountId, $sealId);

        if (!$seal) {
            return null;
        }

        $seal->update(array_filter([
            'seal_name' => $data['seal_name'] ?? null,
            'seal_identifier' => $data['seal_identifier'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($value) => $value !== null));

        return $seal->fresh();
    }

    /**
     * Delete a seal.
     */
    public function deleteSeal(int $accountId, string $sealId): bool
    {
        $seal = $this->getSeal($accountId, $sealId);

        if (!$seal) {
            return false;
        }

        return $seal->delete();
    }
}
