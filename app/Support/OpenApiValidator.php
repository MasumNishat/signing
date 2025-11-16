<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * OpenAPI Schema Validator
 *
 * Validates API responses against OpenAPI 3.0 specification schemas.
 */
class OpenApiValidator
{
    protected array $spec;
    protected array $errors = [];

    public function __construct(?string $specPath = null)
    {
        $specPath = $specPath ?? base_path('docs/openapi.json');

        if (!file_exists($specPath)) {
            throw new \RuntimeException("OpenAPI specification not found at: {$specPath}");
        }

        $this->spec = json_decode(file_get_contents($specPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON in OpenAPI specification: ' . json_last_error_msg());
        }
    }

    /**
     * Validate a response against the OpenAPI spec
     *
     * @param string $path API path (e.g., /api/v2.1/accounts/{accountId}/envelopes)
     * @param string $method HTTP method (get, post, put, delete, etc.)
     * @param int $statusCode HTTP status code
     * @param array $responseData Response data to validate
     * @return bool
     */
    public function validateResponse(string $path, string $method, int $statusCode, array $responseData): bool
    {
        $this->errors = [];

        // Normalize path to match OpenAPI spec format
        $normalizedPath = $this->normalizePath($path);

        // Find operation in spec
        $operation = $this->findOperation($normalizedPath, strtolower($method));

        if (!$operation) {
            $this->errors[] = "Operation not found in spec: {$method} {$normalizedPath}";
            return false;
        }

        // Get response schema for status code
        $responseSchema = $this->getResponseSchema($operation, $statusCode);

        if (!$responseSchema) {
            $this->errors[] = "Response schema not found for status code: {$statusCode}";
            return false;
        }

        // Validate response data against schema
        return $this->validateDataAgainstSchema($responseData, $responseSchema);
    }

    /**
     * Validate request data against the OpenAPI spec
     *
     * @param string $path API path
     * @param string $method HTTP method
     * @param array $requestData Request data to validate
     * @return bool
     */
    public function validateRequest(string $path, string $method, array $requestData): bool
    {
        $this->errors = [];

        $normalizedPath = $this->normalizePath($path);
        $operation = $this->findOperation($normalizedPath, strtolower($method));

        if (!$operation) {
            $this->errors[] = "Operation not found in spec: {$method} {$normalizedPath}";
            return false;
        }

        // Get request body schema
        $requestSchema = $this->getRequestSchema($operation);

        if (!$requestSchema) {
            // No request body defined, check if data is empty
            return empty($requestData);
        }

        return $this->validateDataAgainstSchema($requestData, $requestSchema);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Normalize API path to match OpenAPI spec format
     */
    protected function normalizePath(string $path): string
    {
        // Remove /api/v2.1 prefix if present
        $path = preg_replace('#^/api/v2\.1#', '', $path);

        // Replace actual IDs with parameter placeholders
        $path = preg_replace('#/[a-f0-9-]{36}#i', '/{id}', $path);
        $path = preg_replace('#/accounts/[^/]+#', '/accounts/{accountId}', $path);
        $path = preg_replace('#/envelopes/([a-f0-9-]{36})#i', '/envelopes/{envelopeId}', $path);
        $path = preg_replace('#/templates/([a-f0-9-]{36})#i', '/templates/{templateId}', $path);
        $path = preg_replace('#/users/([a-f0-9-]{36})#i', '/users/{userId}', $path);

        return $path;
    }

    /**
     * Find operation definition in OpenAPI spec
     */
    protected function findOperation(string $path, string $method): ?array
    {
        $paths = $this->spec['paths'] ?? [];

        foreach ($paths as $specPath => $pathItem) {
            if ($this->pathsMatch($path, $specPath)) {
                return $pathItem[$method] ?? null;
            }
        }

        return null;
    }

    /**
     * Check if two paths match (accounting for parameters)
     */
    protected function pathsMatch(string $path1, string $path2): bool
    {
        // Simple exact match first
        if ($path1 === $path2) {
            return true;
        }

        // Convert both to regex patterns
        $pattern1 = $this->pathToRegex($path1);
        $pattern2 = $this->pathToRegex($path2);

        return preg_match($pattern1, $path2) || preg_match($pattern2, $path1);
    }

    /**
     * Convert path with parameters to regex pattern
     */
    protected function pathToRegex(string $path): string
    {
        $pattern = preg_replace('#\{[^}]+\}#', '[^/]+', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Get response schema for status code
     */
    protected function getResponseSchema(array $operation, int $statusCode): ?array
    {
        $responses = $operation['responses'] ?? [];

        // Try exact status code
        if (isset($responses[$statusCode])) {
            return $this->extractSchema($responses[$statusCode]);
        }

        // Try wildcard (e.g., 2XX, 4XX)
        $wildcardCode = substr((string)$statusCode, 0, 1) . 'XX';
        if (isset($responses[$wildcardCode])) {
            return $this->extractSchema($responses[$wildcardCode]);
        }

        // Try default
        if (isset($responses['default'])) {
            return $this->extractSchema($responses['default']);
        }

        return null;
    }

    /**
     * Get request body schema
     */
    protected function getRequestSchema(array $operation): ?array
    {
        $requestBody = $operation['requestBody'] ?? null;

        if (!$requestBody) {
            return null;
        }

        $content = $requestBody['content'] ?? [];
        $jsonContent = $content['application/json'] ?? null;

        if (!$jsonContent) {
            return null;
        }

        return $jsonContent['schema'] ?? null;
    }

    /**
     * Extract schema from response definition
     */
    protected function extractSchema(array $response): ?array
    {
        $content = $response['content'] ?? [];
        $jsonContent = $content['application/json'] ?? null;

        if (!$jsonContent) {
            return null;
        }

        return $jsonContent['schema'] ?? null;
    }

    /**
     * Validate data against schema
     */
    protected function validateDataAgainstSchema(array $data, array $schema, string $path = ''): bool
    {
        $valid = true;

        // Handle $ref references
        if (isset($schema['$ref'])) {
            $schema = $this->resolveRef($schema['$ref']);
        }

        // Validate required properties
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $requiredField) {
                if (!array_key_exists($requiredField, $data)) {
                    $this->errors[] = "Missing required field: {$path}.{$requiredField}";
                    $valid = false;
                }
            }
        }

        // Validate properties
        if (isset($schema['properties'])) {
            foreach ($data as $key => $value) {
                $propertySchema = $schema['properties'][$key] ?? null;

                if ($propertySchema) {
                    if (!$this->validateValue($value, $propertySchema, "{$path}.{$key}")) {
                        $valid = false;
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * Validate individual value against schema
     */
    protected function validateValue($value, array $schema, string $path): bool
    {
        // Handle $ref
        if (isset($schema['$ref'])) {
            $schema = $this->resolveRef($schema['$ref']);
        }

        $type = $schema['type'] ?? null;

        if (!$type) {
            return true; // No type constraint
        }

        // Type validation
        $valid = match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_numeric($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_array($value),
            default => true,
        };

        if (!$valid) {
            $actualType = gettype($value);
            $this->errors[] = "Type mismatch at {$path}: expected {$type}, got {$actualType}";
            return false;
        }

        // Additional validations
        if ($type === 'string') {
            if (isset($schema['minLength']) && strlen($value) < $schema['minLength']) {
                $this->errors[] = "String too short at {$path}: minimum length {$schema['minLength']}";
                return false;
            }

            if (isset($schema['maxLength']) && strlen($value) > $schema['maxLength']) {
                $this->errors[] = "String too long at {$path}: maximum length {$schema['maxLength']}";
                return false;
            }

            if (isset($schema['pattern']) && !preg_match($schema['pattern'], $value)) {
                $this->errors[] = "String does not match pattern at {$path}";
                return false;
            }

            if (isset($schema['enum']) && !in_array($value, $schema['enum'])) {
                $this->errors[] = "Value not in enum at {$path}";
                return false;
            }
        }

        if ($type === 'array' && isset($schema['items'])) {
            foreach ($value as $index => $item) {
                if (!$this->validateValue($item, $schema['items'], "{$path}[{$index}]")) {
                    return false;
                }
            }
        }

        if ($type === 'object' && is_array($value)) {
            return $this->validateDataAgainstSchema($value, $schema, $path);
        }

        return true;
    }

    /**
     * Resolve $ref reference
     */
    protected function resolveRef(string $ref): array
    {
        // Handle #/components/schemas/SchemaName format
        if (str_starts_with($ref, '#/')) {
            $path = substr($ref, 2); // Remove #/
            $parts = explode('/', $path);

            $current = $this->spec;
            foreach ($parts as $part) {
                $current = $current[$part] ?? null;
                if ($current === null) {
                    return [];
                }
            }

            return $current;
        }

        return [];
    }
}
