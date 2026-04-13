<?php

namespace App\Exceptions;

use App\Exceptions\ApiException;

class ConflictException extends ApiException
{
    protected int $status = 409;
}
