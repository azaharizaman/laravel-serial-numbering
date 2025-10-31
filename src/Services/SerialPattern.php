<?php

namespace AzahariZaman\ControlledNumber\Services;

use AzahariZaman\ControlledNumber\Exceptions\InvalidPatternException;

class SerialPattern
{
    protected string $pattern;
    protected array $segments = [];
    protected array $config;

    public function __construct(string $pattern, array $config = [])
    {
        $this->pattern = $pattern;
        $this->config = $config;
        $this->parsePattern();
    }

    /**
     * Parse the pattern and extract segments.
     */
    protected function parsePattern(): void
    {
        // Match all segments in the pattern like {year}, {month}, {model.property}
        preg_match_all('/\{([^}]+)\}/', $this->pattern, $matches);
        
        if (!empty($matches[1])) {
            $this->segments = $matches[1];
        }
    }

    /**
     * Get all segments from the pattern.
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * Get the raw pattern string.
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Get pattern configuration.
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    /**
     * Validate the pattern structure.
     *
     * @throws InvalidPatternException
     */
    public function validate(): bool
    {
        // Check if pattern is empty
        if (empty($this->pattern)) {
            throw new InvalidPatternException($this->pattern, 'Pattern cannot be empty');
        }

        // Check if pattern has at least one segment
        if (empty($this->segments)) {
            throw new InvalidPatternException($this->pattern, 'Pattern must contain at least one segment (e.g., {year}, {number})');
        }

        // Check if pattern contains {number} segment
        if (!in_array('number', $this->segments)) {
            throw new InvalidPatternException($this->pattern, 'Pattern must contain {number} segment');
        }

        // Validate segment names (alphanumeric and dots only)
        foreach ($this->segments as $segment) {
            if (!preg_match('/^[a-zA-Z0-9_.]+$/', $segment)) {
                throw new InvalidPatternException($this->pattern, "Invalid segment name: {$segment}");
            }
        }

        return true;
    }

    /**
     * Build the serial number by replacing segments with their values.
     */
    public function build(array $resolvedSegments): string
    {
        $serial = $this->pattern;

        foreach ($resolvedSegments as $segment => $value) {
            $serial = str_replace("{{$segment}}", $value, $serial);
        }

        return $serial;
    }

    /**
     * Extract the numeric portion configuration.
     */
    public function getNumberConfig(): array
    {
        return [
            'start' => $this->config['start'] ?? 1,
            'digits' => $this->config['digits'] ?? 4,
        ];
    }

    /**
     * Format the number according to configuration.
     */
    public function formatNumber(int $number): string
    {
        $digits = $this->config['digits'] ?? 4;
        return str_pad((string)$number, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Check if pattern contains a specific segment.
     */
    public function hasSegment(string $segment): bool
    {
        return in_array($segment, $this->segments);
    }

    /**
     * Get all model property segments (e.g., department.code, user.name).
     */
    public function getModelSegments(): array
    {
        return array_filter($this->segments, function ($segment) {
            return strpos($segment, '.') !== false;
        });
    }

    /**
     * Get all date/time segments.
     */
    public function getDateTimeSegments(): array
    {
        $dateTimeSegments = ['year', 'month', 'day', 'hour', 'minute', 'second', 'week'];
        
        return array_filter($this->segments, function ($segment) use ($dateTimeSegments) {
            return in_array($segment, $dateTimeSegments);
        });
    }
}
