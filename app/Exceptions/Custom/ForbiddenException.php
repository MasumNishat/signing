<?php

namespace App\Exceptions\Custom;

class ForbiddenException extends ApiException
{
    public function __construct(
        string $message = 'You do not have permission to perform this action',
        ?string $permission = null,
        ?\Throwable $previous = null
    ) {
        $details = $permission ? ['required_permission' => $permission] : null;

        parent::__construct(
            $message,
            'FORBIDDEN',
            403,
            $details,
            $previous
        );
    }
}
