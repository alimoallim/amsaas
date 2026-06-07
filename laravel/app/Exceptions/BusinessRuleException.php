<?php

namespace App\Exceptions;

use Exception;

class BusinessRuleException extends Exception
{
    public readonly ?string $errorCode;

    public function __construct(
        string $message,
        ?string $errorCode = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        $this->errorCode = $errorCode;

        parent::__construct($message, $code, $previous);
    }
}
