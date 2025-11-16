<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\BrandLogo;
use App\Models\BrandResource;
use App\Models\BrandEmailContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_brands()
    {
        $this->createAndAuthenticateUser();

        Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Company Brand',
            'is_default' => false,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_brand()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands", [
            'brand_name' => 'New Brand',
            'brand_company' => 'Acme Corp',
            'is_sending_default' => false,
            'is_signing_default' => false,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('brands', [
            'account_id' => $this->account->id,
            'brand_name' => 'New Brand',
        ]);
    }

    /** @test */
    public function test_can_get_specific_brand()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.brand_name', 'Test Brand');
    }

    /** @test */
    public function test_can_update_brand()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Original',
            'is_default' => false,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}", [
            'brand_name' => 'Updated Brand',
            'brand_company' => 'New Company',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'brand_name' => 'Updated Brand',
        ]);
    }

    /** @test */
    public function test_can_delete_brand()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'To Delete',
            'is_default' => false,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('brands', [
            'id' => $brand->id,
        ]);
    }

    /** @test */
    public function test_can_upload_brand_logo()
    {
        Storage::fake('brands');
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $file = UploadedFile::fake()->image('logo.png', 300, 100);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/logos", [
            'logo_type' => 'primary',
            'logo_file' => $file,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('brand_logos', [
            'brand_id' => $brand->id,
            'logo_type' => 'primary',
        ]);
    }

    /** @test */
    public function test_can_list_brand_logos()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        BrandLogo::create([
            'brand_id' => $brand->id,
            'logo_type' => 'primary',
            'file_path' => 'path/to/logo.png',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/logos");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_brand_logo()
    {
        Storage::fake('brands');
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $logo = BrandLogo::create([
            'brand_id' => $brand->id,
            'logo_type' => 'primary',
            'file_path' => 'old/logo.png',
        ]);

        $file = UploadedFile::fake()->image('new-logo.png', 300, 100);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/logos/{$logo->id}", [
            'logo_file' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_get_brand_resources()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        BrandResource::create([
            'brand_id' => $brand->id,
            'resource_type' => 'template',
            'content' => 'Sample content',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/resources");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_brand_email_content()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/email_content", [
            'email_subject' => 'Custom Subject',
            'email_body' => 'Custom email body content',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('brand_email_contents', [
            'brand_id' => $brand->id,
            'email_subject' => 'Custom Subject',
        ]);
    }

    /** @test */
    public function test_can_get_brand_email_content()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        BrandEmailContent::create([
            'brand_id' => $brand->id,
            'email_subject' => 'Test Subject',
            'email_body' => 'Test body',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/email_content");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_export_brand_to_file()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Export Brand',
            'is_default' => false,
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/export");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertArrayHasKey('export_data', $response->json('data'));
    }

    /** @test */
    public function test_can_bulk_delete_brands()
    {
        $this->createAndAuthenticateUser();

        $brand1 = Brand::create(['account_id' => $this->account->id, 'brand_name' => 'Brand 1', 'is_default' => false]);
        $brand2 = Brand::create(['account_id' => $this->account->id, 'brand_name' => 'Brand 2', 'is_default' => false]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/brands", [
            'brand_ids' => [$brand1->brand_id, $brand2->brand_id],
        ]);

        $response->assertStatus(204);

        $this->assertSoftDeleted('brands', ['id' => $brand1->id]);
        $this->assertSoftDeleted('brands', ['id' => $brand2->id]);
    }

    /** @test */
    public function test_validates_brand_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands", [
            'brand_company' => 'Acme Corp',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['brand_name']);
    }

    /** @test */
    public function test_validates_logo_file_type()
    {
        Storage::fake('brands');
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $file = UploadedFile::fake()->create('logo.txt', 100); // Invalid type

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/logos", [
            'logo_type' => 'primary',
            'logo_file' => $file,
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['logo_file']);
    }

    /** @test */
    public function test_supports_multiple_logo_types()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $logoTypes = ['primary', 'secondary', 'email'];

        foreach ($logoTypes as $type) {
            BrandLogo::create([
                'brand_id' => $brand->id,
                'logo_type' => $type,
                'file_path' => "path/to/{$type}.png",
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}/logos");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(3, 'data.logos');
    }

    /** @test */
    public function test_can_set_brand_as_default()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Default Brand',
            'is_default' => false,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/brands/{$brand->brand_id}", [
            'is_sending_default' => true,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'is_sending_default' => true,
        ]);
    }
}
