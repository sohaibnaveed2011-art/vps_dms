<?php

namespace App\Exceptions;

use App\Exceptions\ApiException;

class UnauthorizedException extends ApiException
{
    protected int $status = 401;
}
