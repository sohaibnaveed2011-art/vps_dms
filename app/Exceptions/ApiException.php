<?php

namespace App\Exceptions;

use Exception;

abstract class ApiException extends Exception
{
    protected int $status = 400;

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrors(): array
    {
        return [
            'message' => $this->getMessage(),
        ];
    }
}
