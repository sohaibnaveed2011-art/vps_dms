<?php

namespace App\Exceptions;

use Exception;

class WorkflowException extends Exception
{
    protected $code = 422;
    
    public function __construct(string $message)
    {
        parent::__construct($message, $this->code);
    }
}