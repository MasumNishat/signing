<?php

namespace App\Exceptions\Custom;

class BusinessLogicException extends ApiException
{
    public function __construct(
        string $message,
        string $errorCode,
        mixed $details = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $errorCode,
            400,
            $details,
            $previous
        );
    }
}
