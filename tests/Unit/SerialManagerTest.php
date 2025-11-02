<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Events\SerialNumberGenerated;
use AzahariZaman\ControlledNumber\Exceptions\InvalidPatternException;
use AzahariZaman\ControlledNumber\Exceptions\SerialCollisionException;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Tests\Support\TestAuth;
use AzahariZaman\ControlledNumber\Tests\Support\TestConfig;
use AzahariZaman\ControlledNumber\Tests\Support\TestEvents;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Carbon\Carbon;

class SerialManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestConfig::set('serial-pattern.patterns', [
            'invoice' => [
                'pattern' => 'INV-{number}',
                'start' => 100,
                'digits' => 5,
                'reset' => 'never',
            ],
        ]);

        TestConfig::set('serial-pattern.lock.enabled', false);
        TestConfig::set('serial-pattern.logging.enabled', false);
        TestConfig::set('serial-pattern.lock.timeout', 5);
    }

    protected function createManager(): SerialManager
    {
        return new SerialManager(new SegmentResolver());
    }

    public function test_register_and_check_patterns(): void
    {
        $manager = $this->createManager();

        $manager->registerPattern('order', [
            'pattern' => 'ORD-{number}',
            'start' => 1,
            'digits' => 3,
            'reset' => 'never',
        ]);

        $this->assertTrue($manager->hasPattern('invoice'));
        $this->assertTrue($manager->hasPattern('order'));
        $this->assertArrayHasKey('order', $manager->getPatterns());
    }

    public function test_preview_uses_starting_number_when_no_sequence_exists(): void
    {
        $manager = $this->createManager();
        $preview = $manager->preview('invoice');

        $this->assertSame('INV-00100', $preview);
    }

    public function test_preview_increments_based_on_existing_sequence(): void
    {
        SerialSequence::create([
            'name' => 'invoice',
            'pattern' => 'INV-{number}',
            'current_number' => 150,
            'reset_type' => 'never',
        ]);

        $manager = $this->createManager();
        $preview = $manager->preview('invoice');

        $this->assertSame('INV-00151', $preview);
    }

    public function test_reset_sequence_updates_current_number(): void
    {
        $sequence = SerialSequence::create([
            'name' => 'invoice',
            'pattern' => 'INV-{number}',
            'current_number' => 250,
            'reset_type' => 'never',
        ]);

        $manager = $this->createManager();
        $result = $manager->resetSequence('invoice', 10);

        $sequence->refresh();

        $this->assertTrue($result);
        $this->assertSame(10, $sequence->current_number);
    }

    public function test_reset_sequence_returns_false_when_missing(): void
    {
        $manager = $this->createManager();
        $this->assertFalse($manager->resetSequence('missing'));
    }

    public function test_void_marks_serial_log_as_void(): void
    {
        $log = SerialLog::create([
            'serial' => 'INV-VO-001',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => false,
        ]);

        $manager = $this->createManager();
        $this->assertTrue($manager->void('INV-VO-001', 'cancelled'));

        $log->refresh();
        $this->assertTrue($log->is_void);
        $this->assertSame('cancelled', $log->void_reason);
    }

    public function test_void_returns_false_for_missing_serial(): void
    {
        $manager = $this->createManager();
        $this->assertFalse($manager->void('UNKNOWN'));
    }

    public function test_generate_throws_when_pattern_missing(): void
    {
        $manager = $this->createManager();

        $this->expectException(InvalidPatternException::class);
        $manager->generate('missing');
    }

    public function test_ensure_uniqueness_detects_collision(): void
    {
        TestConfig::set('serial-pattern.logging.enabled', true);

        SerialLog::create([
            'serial' => 'INV-999',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => false,
        ]);

        $manager = $this->createManager();
        $method = (new \ReflectionClass($manager))->getMethod('ensureUniqueness');
        $method->setAccessible(true);

        $this->expectException(SerialCollisionException::class);
        $method->invoke($manager, 'INV-999', 'invoice');
    }

    public function test_log_serial_tracks_user_and_dispatches_event(): void
    {
        TestConfig::set('serial-pattern.logging.enabled', true);
        TestConfig::set('serial-pattern.logging.track_user', true);
        TestAuth::actingAs(42);

        $manager = $this->createManager();
        $method = (new \ReflectionClass($manager))->getMethod('logSerial');
        $method->setAccessible(true);

        $log = $method->invoke($manager, 'INV-555', 'invoice', null);

        $this->assertInstanceOf(SerialLog::class, $log);
        $this->assertSame(42, $log->user_id);
        $this->assertNotEmpty(TestEvents::$events);
        $this->assertInstanceOf(SerialNumberGenerated::class, TestEvents::$events[0]);
    }
}
