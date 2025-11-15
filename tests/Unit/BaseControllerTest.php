<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V2_1\BaseController;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class BaseControllerTest extends TestCase
{
    protected BaseController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an anonymous subclass since BaseController is abstract
        $this->controller = new class extends BaseController {
            // Expose protected methods for testing
            public function testSuccessResponse($data = null, $message = null, $code = 200): JsonResponse
            {
                return $this->successResponse($data, $message, $code);
            }

            public function testErrorResponse($message, $code = 400, $errors = null, $errorCode = null): JsonResponse
            {
                return $this->errorResponse($message, $code, $errors, $errorCode);
            }
        };
    }

    public function test_success_response_structure(): void
    {
        $response = $this->controller->testSuccessResponse(['test' => 'data'], 'Success message');

        $data = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals(['test' => 'data'], $data['data']);
        $this->assertEquals('Success message', $data['message']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('timestamp', $data['meta']);
        $this->assertArrayHasKey('request_id', $data['meta']);
        $this->assertEquals('v2.1', $data['meta']['version']);
    }

    public function test_error_response_structure(): void
    {
        $response = $this->controller->testErrorResponse('Error message', 400, ['field' => 'error'], 'ERROR_CODE');

        $data = $response->getData(true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Error message', $data['error']['message']);
        $this->assertEquals('ERROR_CODE', $data['error']['code']);
        $this->assertEquals(['field' => 'error'], $data['error']['details']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function test_metadata_includes_required_fields(): void
    {
        $response = $this->controller->testSuccessResponse();
        $data = $response->getData(true);

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('timestamp', $data['meta']);
        $this->assertArrayHasKey('request_id', $data['meta']);
        $this->assertArrayHasKey('version', $data['meta']);
        $this->assertEquals('v2.1', $data['meta']['version']);
    }
}
