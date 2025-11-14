<?php

namespace App\Exceptions\Custom;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected mixed $details;

    public function __construct(
        string $message,
        string $errorCode,
        int $statusCode = 400,
        mixed $details = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }

    public function render(): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->message,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()->header('X-Request-ID') ?? \Str::uuid()->toString(),
                'version' => 'v2.1',
            ],
        ];

        if ($this->details !== null) {
            $response['error']['details'] = $this->details;
        }

        return response()->json($response, $this->statusCode);
    }
}
