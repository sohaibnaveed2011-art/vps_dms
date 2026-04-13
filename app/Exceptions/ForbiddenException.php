<?php

namespace App\Exceptions;

use App\Exceptions\ApiException;

class ForbiddenException extends ApiException
{
    protected int $status = 403;
}
