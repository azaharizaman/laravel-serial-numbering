<?php

namespace AzahariZaman\ControlledNumber\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SegmentInterface
{
    /**
     * Resolve the segment value.
     *
     * @param  Model|null  $model  The model instance for context
     * @param  array  $context  Additional context data
     * @return string
     */
    public function resolve(?Model $model = null, array $context = []): string;

    /**
     * Get the segment name/identifier.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Validate the segment configuration.
     *
     * @return bool
     */
    public function validate(): bool;
}
