<?php

namespace AzahariZaman\ControlledNumber\Traits;

trait LogsSerialActivity
{
    /**
     * Log serial number activity using Spatie Activity Log.
     *
     * @param string $description
     * @param array $properties
     * @param mixed $subject
     * @return void
     */
    protected function logActivity(string $description, array $properties = [], $subject = null): void
    {
        if (!config('serial-pattern.logging.activity_log.enabled', false)) {
            return;
        }

        if (!class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            return;
        }

        $activity = activity(config('serial-pattern.logging.activity_log.log_name', 'serial'));

        if ($subject) {
            $activity->performedOn($subject);
        }

        if (config('serial-pattern.logging.track_user', true) && function_exists('auth') && auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        // Add tenant_id if available (multi-tenant support)
        if (function_exists('tenant') && tenant()) {
            $properties['tenant_id'] = tenant()->id;
        }

        if (config('serial-pattern.logging.activity_log.include_properties', true) && !empty($properties)) {
            $activity->withProperties($properties);
        }

        $activity->log($description);
    }
}
