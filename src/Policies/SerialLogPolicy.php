<?php

namespace Azahari\SerialPattern\Policies;

use Azahari\SerialPattern\Models\SerialLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class SerialLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any serial logs.
     */
    public function viewAny($user): bool
    {
        // Customize this based on your authorization requirements
        return true;
    }

    /**
     * Determine whether the user can view the serial log.
     */
    public function view($user, SerialLog $serialLog): bool
    {
        // Customize this based on your authorization requirements
        return true;
    }

    /**
     * Determine whether the user can void the serial log.
     */
    public function void($user, SerialLog $serialLog): bool
    {
        // Only allow voiding if not already voided
        if ($serialLog->is_void) {
            return false;
        }

        // Customize this based on your authorization requirements
        return true;
    }

    /**
     * Determine whether the user can delete the serial log.
     */
    public function delete($user, SerialLog $serialLog): bool
    {
        // Serial logs cannot be deleted for audit trail integrity
        return false;
    }
}
