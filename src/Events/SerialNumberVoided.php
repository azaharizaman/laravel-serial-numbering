<?php

namespace AzahariZaman\ControlledNumber\Events;

use AzahariZaman\ControlledNumber\Models\SerialLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SerialNumberVoided
{
    use Dispatchable, SerializesModels;

    public SerialLog $log;
    public string $serial;
    public ?string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(SerialLog $log, string $serial, ?string $reason = null)
    {
        $this->log = $log;
        $this->serial = $serial;
        $this->reason = $reason;
    }
}
