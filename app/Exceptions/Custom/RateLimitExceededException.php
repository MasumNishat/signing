<?php

namespace App\Exceptions\Custom;

class RateLimitExceededException extends ApiException
{
    public function __construct(
        int $retryAfter,
        string $message = 'Too many requests. Please try again later',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'RATE_LIMIT_EXCEEDED',
            429,
            ['retry_after' => $retryAfter],
            $previous
        );
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        $response = parent::render();
        $retryAfter = $this->details['retry_after'] ?? 60;

        $response->header('Retry-After', $retryAfter);
        $response->header('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);

        return $response;
    }
}
