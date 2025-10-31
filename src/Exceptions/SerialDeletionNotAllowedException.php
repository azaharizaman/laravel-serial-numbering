<?php

namespace Azahari\SerialPattern\Exceptions;

use Exception;

class SerialDeletionNotAllowedException extends Exception
{
    /**
     * Create a new serial deletion not allowed exception.
     */
    public function __construct(string $message = 'Serial logs cannot be deleted for audit trail integrity.')
    {
        parent::__construct($message);
    }
}
