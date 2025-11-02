<?php

namespace AzahariZaman\ControlledNumber\Tests\Support {

    class TestAuth
    {
        public static bool $authenticated = false;
        public static ?int $id = null;

        public static function actingAs(int $userId): void
        {
            self::$authenticated = true;
            self::$id = $userId;
        }

        public static function guest(): void
        {
            self::$authenticated = false;
            self::$id = null;
        }
    }

    class TestEvents
    {
        /** @var array<int, object> */
        public static array $events = [];

        public static function record(object $event): void
        {
            self::$events[] = $event;
        }

        public static function reset(): void
        {
            self::$events = [];
        }
    }
}

namespace {
    function auth()
    {
        return new class {
            public function check(): bool
            {
                return \AzahariZaman\ControlledNumber\Tests\Support\TestAuth::$authenticated;
            }

            public function id(): ?int
            {
                return \AzahariZaman\ControlledNumber\Tests\Support\TestAuth::$id;
            }
        };
    }

    function event(object $event)
    {
        \AzahariZaman\ControlledNumber\Tests\Support\TestEvents::record($event);
        return $event;
    }
}
