<?php

namespace App\Exceptions\Custom;

class ValidationException extends ApiException
{
    public function __construct(
        array $errors,
        string $message = 'The given data was invalid',
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            'VALIDATION_ERROR',
            422,
            $errors,
            $previous
        );
    }
}
