<?php

namespace Azahari\SerialPattern\Observers;

use Azahari\SerialPattern\Models\SerialLog;
use Illuminate\Support\Facades\Log;

class SerialLogObserver
{
    /**
     * Handle the SerialLog "created" event.
     */
    public function created(SerialLog $serialLog): void
    {
        Log::info('Serial number generated', [
            'serial' => $serialLog->serial,
            'pattern' => $serialLog->pattern_name,
            'user_id' => $serialLog->user_id,
        ]);
    }

    /**
     * Handle the SerialLog "updated" event.
     */
    public function updated(SerialLog $serialLog): void
    {
        if ($serialLog->isDirty('is_void') && $serialLog->is_void) {
            Log::warning('Serial number voided', [
                'serial' => $serialLog->serial,
                'pattern' => $serialLog->pattern_name,
                'reason' => $serialLog->void_reason,
                'user_id' => $serialLog->user_id,
            ]);
        }
    }
}
