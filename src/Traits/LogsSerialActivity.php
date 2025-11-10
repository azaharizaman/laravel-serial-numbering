<?php

namespace AzahariZaman\ControlledNumber\Traits;

trait LogsSerialActivity
{
    /**
     * Log serial number activity using Spatie Activity Log or fallback to Laravel Log.
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

        // Try Spatie Activity Log if available
        if ($this->logWithSpatie($description, $properties, $subject)) {
            return;
        }

        // Fallback to default Laravel logging
        $this->logWithLaravel($description, $properties, $subject);
    }

    /**
     * Log activity using Spatie Activity Log package.
     *
     * @param string $description
     * @param array $properties
     * @param mixed $subject
     * @return bool True if logged successfully, false if Spatie is not available
     */
    protected function logWithSpatie(string $description, array $properties, $subject): bool
    {
        if (!class_exists(\Spatie\Activitylog\Facades\Activity::class)) {
            return false;
        }

        $activity = activity(config('serial-pattern.logging.activity_log.log_name', 'serial'));

        if ($subject) {
            $activity->performedOn($subject);
        }

        if (config('serial-pattern.logging.track_user', true) && function_exists('auth') && auth()->check()) {
            $activity->causedBy(auth()->user());
        }

        // Add tenant_id using configurable resolver
        $tenantId = $this->resolveTenantId();
        if ($tenantId !== null) {
            $properties['tenant_id'] = $tenantId;
        }

        if (config('serial-pattern.logging.activity_log.include_properties', true) && !empty($properties)) {
            $activity->withProperties($properties);
        }

        $activity->log($description);
        
        return true;
    }

    /**
     * Fallback logging using Laravel's default logger.
     *
     * @param string $description
     * @param array $properties
     * @param mixed $subject
     * @return void
     */
    protected function logWithLaravel(string $description, array $properties, $subject): void
    {
        $context = $properties;
        
        if ($subject) {
            $context['subject_type'] = get_class($subject);
            $context['subject_id'] = $subject->getKey();
        }

        if (config('serial-pattern.logging.track_user', true) && function_exists('auth') && auth()->check()) {
            $context['user_id'] = auth()->id();
        }

        // Add tenant_id using configurable resolver
        $tenantId = $this->resolveTenantId();
        if ($tenantId !== null) {
            $context['tenant_id'] = $tenantId;
        }

        \Illuminate\Support\Facades\Log::info("[Serial Activity] {$description}", $context);
    }

    /**
     * Resolve tenant ID using configured resolver or fallback methods.
     *
     * @return mixed|null
     */
    protected function resolveTenantId()
    {
        // Use custom tenant resolver if configured
        $tenantResolver = config('serial-pattern.logging.activity_log.tenant_resolver');
        if ($tenantResolver && is_callable($tenantResolver)) {
            return $tenantResolver();
        }

        // Fallback: Try container-bound tenant
        if (app()->bound('tenant')) {
            $tenant = app('tenant');
            if ($tenant && isset($tenant->id)) {
                return $tenant->id;
            }
        }

        // Fallback: Try global tenant() helper function
        if (function_exists('tenant') && tenant()) {
            return tenant()->id;
        }

        return null;
    }
}
