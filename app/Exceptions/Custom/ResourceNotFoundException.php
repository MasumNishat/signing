<?php

namespace App\Exceptions\Custom;

class ResourceNotFoundException extends ApiException
{
    public function __construct(
        string $resource = 'Resource',
        ?string $identifier = null,
        ?\Throwable $previous = null
    ) {
        $message = $identifier
            ? "The requested {$resource} with identifier '{$identifier}' was not found"
            : "The requested {$resource} was not found";

        parent::__construct(
            $message,
            'RESOURCE_NOT_FOUND',
            404,
            ['resource' => $resource, 'identifier' => $identifier],
            $previous
        );
    }
}
