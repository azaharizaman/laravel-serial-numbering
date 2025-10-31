<?php

namespace AzahariZaman\ControlledNumber\Events;

use AzahariZaman\ControlledNumber\Models\SerialLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SerialNumberGenerated
{
    use Dispatchable, SerializesModels;

    public SerialLog $log;
    public string $serial;
    public string $patternName;

    /**
     * Create a new event instance.
     */
    public function __construct(SerialLog $log, string $serial, string $patternName)
    {
        $this->log = $log;
        $this->serial = $serial;
        $this->patternName = $patternName;
    }
}
