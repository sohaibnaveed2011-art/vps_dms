<?php

namespace App\Exceptions;

use App\Exceptions\ApiException;

class NotFoundException extends ApiException
{
    protected int $status = 404;
}
