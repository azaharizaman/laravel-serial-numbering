<?php

namespace AzahariZaman\ControlledNumber\Tests\Support {

    class TestApp
    {
        /** @var array<string, mixed> */
        protected static array $instances = [];

        public static function bind(string $abstract, mixed $instance): void
        {
            self::$instances[$abstract] = $instance;
        }

        public static function resolve(string $abstract): mixed
        {
            if (isset(self::$instances[$abstract])) {
                return self::$instances[$abstract];
            }

            if (class_exists($abstract)) {
                return new $abstract();
            }

            throw new \InvalidArgumentException("No test binding found for [{$abstract}]");
        }

        public static function flush(): void
        {
            self::$instances = [];
        }
    }
}

namespace {
    function app($abstract = null)
    {
        if ($abstract === null) {
            return \AzahariZaman\ControlledNumber\Tests\Support\TestApp::class;
        }

        return \AzahariZaman\ControlledNumber\Tests\Support\TestApp::resolve($abstract);
    }
}
