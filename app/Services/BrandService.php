<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Models\Brand;
use App\Models\BrandEmailContent;
use App\Models\BrandLogo;
use App\Models\BrandResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BrandService
 *
 * Handles business logic for brand management including logos, resources,
 * and email content customization.
 */
class BrandService
{
    /**
     * Create a new brand
     *
     * @param int $accountId
     * @param array $data
     * @return Brand
     * @throws ValidationException
     */
    public function createBrand(int $accountId, array $data): Brand
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            if (empty($data['brand_name'])) {
                throw new ValidationException('brand_name is required');
            }

            // Create brand
            $brand = Brand::create([
                'account_id' => $accountId,
                'brand_name' => $data['brand_name'],
                'brand_company' => $data['brand_company'] ?? null,
                'is_sending_default' => $data['is_sending_default'] ?? false,
                'is_signing_default' => $data['is_signing_default'] ?? false,
                'is_overriding_company_name' => $data['is_overriding_company_name'] ?? false,
            ]);

            // Set as default if requested
            if (!empty($data['is_sending_default'])) {
                $brand->setAsSendingDefault();
            }

            if (!empty($data['is_signing_default'])) {
                $brand->setAsSigningDefault();
            }

            DB::commit();

            Log::info('Brand created', [
                'brand_id' => $brand->brand_id,
                'account_id' => $accountId,
            ]);

            return $brand->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create brand', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a brand by ID
     *
     * @param int $accountId
     * @param string $brandId
     * @return Brand
     * @throws ResourceNotFoundException
     */
    public function getBrand(int $accountId, string $brandId): Brand
    {
        $brand = Brand::where('brand_id', $brandId)
            ->where('account_id', $accountId)
            ->with(['logos', 'resources', 'emailContents'])
            ->first();

        if (!$brand) {
            throw new ResourceNotFoundException('Brand not found');
        }

        return $brand;
    }

    /**
     * List brands with filters and pagination
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listBrands(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = Brand::where('account_id', $accountId);

        // Search by name/company
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load relationships
        $query->withCount(['logos', 'resources', 'emailContents']);

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Update a brand
     *
     * @param int $accountId
     * @param string $brandId
     * @param array $data
     * @return Brand
     * @throws ResourceNotFoundException
     */
    public function updateBrand(int $accountId, string $brandId, array $data): Brand
    {
        $brand = $this->getBrand($accountId, $brandId);

        DB::beginTransaction();
        try {
            // Update allowed fields
            $allowedFields = [
                'brand_name',
                'brand_company',
                'is_overriding_company_name',
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $brand->$field = $data[$field];
                }
            }

            $brand->save();

            // Handle default flags separately
            if (isset($data['is_sending_default']) && $data['is_sending_default']) {
                $brand->setAsSendingDefault();
            }

            if (isset($data['is_signing_default']) && $data['is_signing_default']) {
                $brand->setAsSigningDefault();
            }

            DB::commit();

            Log::info('Brand updated', [
                'brand_id' => $brandId,
                'account_id' => $accountId,
            ]);

            return $brand->fresh(['logos', 'resources', 'emailContents']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update brand', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update brand');
        }
    }

    /**
     * Delete a brand
     *
     * @param int $accountId
     * @param string $brandId
     * @return bool
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function deleteBrand(int $accountId, string $brandId): bool
    {
        $brand = $this->getBrand($accountId, $brandId);

        // Prevent deleting default brands
        if ($brand->isSendingDefault() || $brand->isSigningDefault()) {
            throw new BusinessLogicException('Cannot delete default brand');
        }

        DB::beginTransaction();
        try {
            // Delete associated files
            foreach ($brand->logos as $logo) {
                $this->deleteLogoFile($logo);
            }

            foreach ($brand->resources as $resource) {
                $this->deleteResourceFile($resource);
            }

            // Soft delete brand (cascades to logos, resources, emailContents)
            $brand->delete();

            DB::commit();

            Log::info('Brand deleted', [
                'brand_id' => $brandId,
                'account_id' => $accountId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete brand', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete brand');
        }
    }

    /**
     * Upload brand logo
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $logoType
     * @param UploadedFile $file
     * @return BrandLogo
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function uploadLogo(
        int $accountId,
        string $brandId,
        string $logoType,
        UploadedFile $file
    ): BrandLogo {
        $brand = $this->getBrand($accountId, $brandId);

        // Validate logo type
        $validTypes = [
            BrandLogo::LOGO_TYPE_PRIMARY,
            BrandLogo::LOGO_TYPE_SECONDARY,
            BrandLogo::LOGO_TYPE_EMAIL,
        ];

        if (!in_array($logoType, $validTypes)) {
            throw new ValidationException("Invalid logo type: {$logoType}");
        }

        // Validate file
        if (!$file->isValid()) {
            throw new ValidationException('Invalid file upload');
        }

        // Validate file type (images only)
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new ValidationException('Only image files are allowed (JPEG, PNG, GIF, SVG)');
        }

        // Validate file size (5MB max)
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new ValidationException('File size must not exceed 5MB');
        }

        DB::beginTransaction();
        try {
            // Delete existing logo of this type
            $existingLogo = $brand->getLogoByType($logoType);
            if ($existingLogo) {
                $this->deleteLogoFile($existingLogo);
                $existingLogo->delete();
            }

            // Store file
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store("brands/{$brand->id}/logos", 'public');

            // Create logo record
            $logo = BrandLogo::create([
                'brand_id' => $brand->id,
                'logo_type' => $logoType,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            DB::commit();

            Log::info('Brand logo uploaded', [
                'brand_id' => $brandId,
                'logo_type' => $logoType,
                'file_name' => $fileName,
            ]);

            return $logo;

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file on error
            if (isset($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            Log::error('Failed to upload brand logo', [
                'brand_id' => $brandId,
                'logo_type' => $logoType,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessLogicException('Failed to upload logo');
        }
    }

    /**
     * Get brand logo
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $logoType
     * @return BrandLogo
     * @throws ResourceNotFoundException
     */
    public function getLogo(int $accountId, string $brandId, string $logoType): BrandLogo
    {
        $brand = $this->getBrand($accountId, $brandId);
        $logo = $brand->getLogoByType($logoType);

        if (!$logo) {
            throw new ResourceNotFoundException("Logo not found: {$logoType}");
        }

        return $logo;
    }

    /**
     * Delete brand logo
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $logoType
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteLogo(int $accountId, string $brandId, string $logoType): bool
    {
        $brand = $this->getBrand($accountId, $brandId);
        $logo = $brand->getLogoByType($logoType);

        if (!$logo) {
            throw new ResourceNotFoundException("Logo not found: {$logoType}");
        }

        DB::beginTransaction();
        try {
            $this->deleteLogoFile($logo);
            $logo->delete();

            DB::commit();

            Log::info('Brand logo deleted', [
                'brand_id' => $brandId,
                'logo_type' => $logoType,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete brand logo', [
                'brand_id' => $brandId,
                'logo_type' => $logoType,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete logo');
        }
    }

    /**
     * Upload brand resource
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $resourceType
     * @param UploadedFile $file
     * @return BrandResource
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function uploadResource(
        int $accountId,
        string $brandId,
        string $resourceType,
        UploadedFile $file
    ): BrandResource {
        $brand = $this->getBrand($accountId, $brandId);

        // Validate resource type
        $validTypes = [
            BrandResource::CONTENT_TYPE_EMAIL,
            BrandResource::CONTENT_TYPE_SENDING,
            BrandResource::CONTENT_TYPE_SIGNING,
            BrandResource::CONTENT_TYPE_SIGNING_CAPTIVE,
        ];

        if (!in_array($resourceType, $validTypes)) {
            throw new ValidationException("Invalid resource type: {$resourceType}");
        }

        // Validate file
        if (!$file->isValid()) {
            throw new ValidationException('Invalid file upload');
        }

        // Validate file size (10MB max for resources)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new ValidationException('File size must not exceed 10MB');
        }

        DB::beginTransaction();
        try {
            // Delete existing resource of this type
            $existingResource = $brand->getResourceByType($resourceType);
            if ($existingResource) {
                $this->deleteResourceFile($existingResource);
                $existingResource->delete();
            }

            // Store file
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store("brands/{$brand->id}/resources", 'public');

            // Create resource record
            $resource = BrandResource::create([
                'brand_id' => $brand->id,
                'resource_content_type' => $resourceType,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'mime_type' => $file->getMimeType(),
            ]);

            DB::commit();

            Log::info('Brand resource uploaded', [
                'brand_id' => $brandId,
                'resource_type' => $resourceType,
                'file_name' => $fileName,
            ]);

            return $resource;

        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up uploaded file on error
            if (isset($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            Log::error('Failed to upload brand resource', [
                'brand_id' => $brandId,
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessLogicException('Failed to upload resource');
        }
    }

    /**
     * Get brand resource
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $resourceType
     * @return BrandResource
     * @throws ResourceNotFoundException
     */
    public function getResource(int $accountId, string $brandId, string $resourceType): BrandResource
    {
        $brand = $this->getBrand($accountId, $brandId);
        $resource = $brand->getResourceByType($resourceType);

        if (!$resource) {
            throw new ResourceNotFoundException("Resource not found: {$resourceType}");
        }

        return $resource;
    }

    /**
     * Delete brand resource
     *
     * @param int $accountId
     * @param string $brandId
     * @param string $resourceType
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteResource(int $accountId, string $brandId, string $resourceType): bool
    {
        $brand = $this->getBrand($accountId, $brandId);
        $resource = $brand->getResourceByType($resourceType);

        if (!$resource) {
            throw new ResourceNotFoundException("Resource not found: {$resourceType}");
        }

        DB::beginTransaction();
        try {
            $this->deleteResourceFile($resource);
            $resource->delete();

            DB::commit();

            Log::info('Brand resource deleted', [
                'brand_id' => $brandId,
                'resource_type' => $resourceType,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete brand resource', [
                'brand_id' => $brandId,
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete resource');
        }
    }

    /**
     * Get brand email contents
     *
     * @param int $accountId
     * @param string $brandId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ResourceNotFoundException
     */
    public function getEmailContents(int $accountId, string $brandId)
    {
        $brand = $this->getBrand($accountId, $brandId);
        return $brand->emailContents;
    }

    /**
     * Update brand email content
     *
     * @param int $accountId
     * @param string $brandId
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function updateEmailContent(int $accountId, string $brandId, array $data)
    {
        $brand = $this->getBrand($accountId, $brandId);

        if (empty($data['email_contents']) || !is_array($data['email_contents'])) {
            throw new ValidationException('email_contents array is required');
        }

        DB::beginTransaction();
        try {
            foreach ($data['email_contents'] as $contentData) {
                if (empty($contentData['email_content_type'])) {
                    continue;
                }

                BrandEmailContent::updateOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'email_content_type' => $contentData['email_content_type'],
                    ],
                    [
                        'content' => $contentData['content'] ?? null,
                        'email_to_link' => $contentData['email_to_link'] ?? null,
                        'link_text' => $contentData['link_text'] ?? null,
                    ]
                );
            }

            DB::commit();

            Log::info('Brand email content updated', [
                'brand_id' => $brandId,
                'account_id' => $accountId,
            ]);

            return $brand->fresh()->emailContents;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update brand email content', [
                'brand_id' => $brandId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update email content');
        }
    }

    /**
     * Delete logo file from storage
     *
     * @param BrandLogo $logo
     * @return void
     */
    protected function deleteLogoFile(BrandLogo $logo): void
    {
        if ($logo->file_path && Storage::disk('public')->exists($logo->file_path)) {
            Storage::disk('public')->delete($logo->file_path);
        }
    }

    /**
     * Delete resource file from storage
     *
     * @param BrandResource $resource
     * @return void
     */
    protected function deleteResourceFile(BrandResource $resource): void
    {
        if ($resource->file_path && Storage::disk('public')->exists($resource->file_path)) {
            Storage::disk('public')->delete($resource->file_path);
        }
    }
}
