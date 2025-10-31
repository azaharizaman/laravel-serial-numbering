<?php

namespace Azahari\SerialPattern\Exceptions;

use Exception;

class SerialCollisionException extends Exception
{
    /**
     * Create a new serial collision exception.
     */
    public function __construct(string $serial, ?string $patternName = null)
    {
        $message = "Serial number collision detected: '{$serial}'";
        
        if ($patternName) {
            $message .= " for pattern '{$patternName}'";
        }
        
        parent::__construct($message);
    }
}
