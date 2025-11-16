<?php

namespace Tests\Feature;

use App\Models\Signature;
use App\Models\SignatureImage;
use App\Models\Seal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SignatureManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_account_signatures()
    {
        $this->createAndAuthenticateUser();

        Signature::create([
            'account_id' => $this->account->id,
            'signature_name' => 'Account Signature',
            'signature_type' => 'account',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signatures");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_account_signature()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures", [
            'signature_name' => 'Company Signature',
            'signature_type' => 'account',
            'signature_font' => 'lucida_handwriting',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signatures', [
            'account_id' => $this->account->id,
            'signature_name' => 'Company Signature',
        ]);
    }

    /** @test */
    public function test_can_get_specific_account_signature()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'signature_name' => 'Test Signature',
            'signature_type' => 'account',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_account_signature()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'signature_name' => 'Original',
            'signature_type' => 'account',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}", [
            'signature_name' => 'Updated',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signatures', [
            'id' => $signature->id,
            'signature_name' => 'Updated',
        ]);
    }

    /** @test */
    public function test_can_delete_account_signature()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'signature_name' => 'To Delete',
            'signature_type' => 'account',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('signatures', [
            'id' => $signature->id,
        ]);
    }

    /** @test */
    public function test_can_list_user_signatures()
    {
        $this->createAndAuthenticateUser();

        Signature::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'signature_name' => 'My Signature',
            'signature_type' => 'user',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/signatures");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_user_signature()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/signatures", [
            'signature_name' => 'My Personal Signature',
            'signature_type' => 'user',
            'signature_font' => 'freestyle_script',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signatures', [
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_upload_signature_image()
    {
        Storage::fake('signatures');
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'signature_name' => 'Test',
            'signature_type' => 'user',
        ]);

        $file = UploadedFile::fake()->image('signature.png', 200, 100);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}/images", [
            'image_file' => $file,
            'image_type' => 'signature',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signature_images', [
            'signature_id' => $signature->id,
            'image_type' => 'signature',
        ]);
    }

    /** @test */
    public function test_can_upload_signature_image_with_base64()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'signature_name' => 'Test',
            'signature_type' => 'user',
        ]);

        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}/images", [
            'image_base64' => $base64Image,
            'image_type' => 'signature',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_supports_multiple_image_types()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'signature_name' => 'Test',
            'signature_type' => 'user',
        ]);

        $imageTypes = ['signature', 'initials', 'stamp'];

        foreach ($imageTypes as $type) {
            SignatureImage::create([
                'signature_id' => $signature->id,
                'image_type' => $type,
                'file_path' => "path/to/{$type}.png",
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}/images");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(3, 'data.images');
    }

    /** @test */
    public function test_can_list_signature_providers()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signature_providers");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function test_can_list_seals()
    {
        $this->createAndAuthenticateUser();

        Seal::create([
            'account_id' => $this->account->id,
            'seal_name' => 'Corporate Seal',
            'seal_identifier' => 'CORP001',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/seals");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_seal()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/seals", [
            'seal_name' => 'Company Seal',
            'seal_identifier' => 'CS001',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('seals', [
            'account_id' => $this->account->id,
            'seal_name' => 'Company Seal',
        ]);
    }

    /** @test */
    public function test_can_get_specific_seal()
    {
        $this->createAndAuthenticateUser();

        $seal = Seal::create([
            'account_id' => $this->account->id,
            'seal_name' => 'Test Seal',
            'seal_identifier' => 'TS001',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/seals/{$seal->seal_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.seal_name', 'Test Seal');
    }

    /** @test */
    public function test_can_update_seal()
    {
        $this->createAndAuthenticateUser();

        $seal = Seal::create([
            'account_id' => $this->account->id,
            'seal_name' => 'Original Seal',
            'seal_identifier' => 'OS001',
            'status' => 'active',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/seals/{$seal->seal_id}", [
            'seal_name' => 'Updated Seal',
            'status' => 'inactive',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('seals', [
            'id' => $seal->id,
            'seal_name' => 'Updated Seal',
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function test_can_delete_seal()
    {
        $this->createAndAuthenticateUser();

        $seal = Seal::create([
            'account_id' => $this->account->id,
            'seal_name' => 'To Delete',
            'seal_identifier' => 'TD001',
            'status' => 'active',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/seals/{$seal->seal_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('seals', [
            'id' => $seal->id,
        ]);
    }

    /** @test */
    public function test_validates_signature_font()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures", [
            'signature_name' => 'Test',
            'signature_type' => 'account',
            'signature_font' => 'invalid_font',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['signature_font']);
    }

    /** @test */
    public function test_validates_image_upload_size()
    {
        Storage::fake('signatures');
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'signature_name' => 'Test',
            'signature_type' => 'account',
        ]);

        $file = UploadedFile::fake()->create('signature.png', 11000); // 11MB

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures/{$signature->signature_id}/images", [
            'image_file' => $file,
            'image_type' => 'signature',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['image_file']);
    }

    /** @test */
    public function test_seal_auto_generates_id()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/seals", [
            'seal_name' => 'Auto ID Seal',
            'seal_identifier' => 'AIS001',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $seal = Seal::where('seal_name', 'Auto ID Seal')->first();
        $this->assertNotNull($seal->seal_id);
    }
}
