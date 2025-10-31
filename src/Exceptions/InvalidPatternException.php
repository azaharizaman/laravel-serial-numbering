<?php

namespace AzahariZaman\ControlledNumber\Exceptions;

use Exception;

class InvalidPatternException extends Exception
{
    /**
     * Create a new invalid pattern exception.
     */
    public function __construct(string $pattern, string $reason)
    {
        parent::__construct("Invalid pattern '{$pattern}': {$reason}");
    }
}
