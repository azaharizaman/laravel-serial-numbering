<?php

namespace AzahariZaman\ControlledNumber\Tests\Support {

    class TestConfig
    {
        /** @var array<string, mixed> */
        protected static array $items = [];

        public static function set(string $key, mixed $value): void
        {
            $segments = explode('.', $key);
            $array =& self::$items;

            while (count($segments) > 1) {
                $segment = array_shift($segments);
                if (!isset($array[$segment]) || !is_array($array[$segment])) {
                    $array[$segment] = [];
                }

                $array =& $array[$segment];
            }

            $array[array_shift($segments)] = $value;
        }

        public static function get(?string $key = null, mixed $default = null): mixed
        {
            if ($key === null) {
                return self::$items;
            }

            $segments = explode('.', $key);
            $value = self::$items;

            foreach ($segments as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return $default;
                }

                $value = $value[$segment];
            }

            return $value;
        }

        public static function reset(): void
        {
            self::$items = [];
        }
    }
}

namespace {
    function config($key = null, $default = null)
    {
        if ($key === null) {
            return \AzahariZaman\ControlledNumber\Tests\Support\TestConfig::get();
        }

        if (is_array($key)) {
            foreach ($key as $configKey => $value) {
                \AzahariZaman\ControlledNumber\Tests\Support\TestConfig::set($configKey, $value);
            }

            return true;
        }

        return \AzahariZaman\ControlledNumber\Tests\Support\TestConfig::get($key, $default);
    }
}
