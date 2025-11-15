<?php

namespace App\Exceptions\Custom;

class UnauthorizedException extends ApiException
{
    public function __construct(
        string $message = 'Unauthorized access',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'UNAUTHORIZED',
            401,
            null,
            $previous
        );
    }
}
