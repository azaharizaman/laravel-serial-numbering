<?php

namespace Azahari\SerialPattern\Tests\Feature;

use Azahari\SerialPattern\Enums\ResetType;
use Azahari\SerialPattern\Models\SerialLog;
use Azahari\SerialPattern\Models\SerialSequence;
use Azahari\SerialPattern\Services\SerialManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class SerialGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [\Azahari\SerialPattern\SerialPatternServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('serial-pattern.patterns', [
            'invoice' => [
                'pattern' => 'INV-{year}-{month}-{number}',
                'start' => 1000,
                'digits' => 5,
                'reset' => 'monthly',
                'interval' => 1,
            ],
            'order' => [
                'pattern' => 'ORD-{year}{number}',
                'start' => 1,
                'digits' => 4,
                'reset' => 'never',
            ],
        ]);
    }

    public function test_can_generate_serial_number()
    {
        $manager = app(SerialManager::class);
        
        $serial = $manager->generate('invoice');
        
        $this->assertNotEmpty($serial);
        $this->assertStringContainsString('INV', $serial);
        $this->assertStringContainsString(date('Y'), $serial);
        $this->assertStringContainsString(date('m'), $serial);
    }

    public function test_serial_number_increments()
    {
        $manager = app(SerialManager::class);
        
        $serial1 = $manager->generate('order');
        $serial2 = $manager->generate('order');
        
        $this->assertNotEquals($serial1, $serial2);
    }

    public function test_serial_number_is_logged()
    {
        config(['serial-pattern.logging.enabled' => true]);
        
        $manager = app(SerialManager::class);
        $serial = $manager->generate('invoice');
        
        $log = SerialLog::where('serial', $serial)->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('invoice', $log->pattern_name);
        $this->assertFalse($log->is_void);
    }

    public function test_can_void_serial_number()
    {
        config(['serial-pattern.logging.enabled' => true]);
        
        $manager = app(SerialManager::class);
        $serial = $manager->generate('invoice');
        
        $result = $manager->void($serial, 'Testing void');
        
        $this->assertTrue($result);
        
        $log = SerialLog::where('serial', $serial)->first();
        $this->assertTrue($log->is_void);
        $this->assertEquals('Testing void', $log->void_reason);
        $this->assertNotNull($log->voided_at);
    }

    public function test_sequence_is_created()
    {
        $manager = app(SerialManager::class);
        $manager->generate('invoice');
        
        $sequence = SerialSequence::where('name', 'invoice')->first();
        
        $this->assertNotNull($sequence);
        $this->assertEquals('invoice', $sequence->name);
        $this->assertEquals(ResetType::MONTHLY, $sequence->reset_type);
    }

    public function test_can_preview_serial_number()
    {
        $manager = app(SerialManager::class);
        
        $preview1 = $manager->preview('order');
        $preview2 = $manager->preview('order');
        
        // Preview should not change the sequence
        $this->assertEquals($preview1, $preview2);
        
        // Actual generation should differ from preview
        $actual = $manager->generate('order');
        $this->assertEquals($preview1, $actual);
    }

    public function test_can_reset_sequence()
    {
        $manager = app(SerialManager::class);
        
        $manager->generate('order');
        $manager->generate('order');
        
        $sequence = SerialSequence::where('name', 'order')->first();
        $currentNumber = $sequence->current_number;
        
        $this->assertTrue($currentNumber > 1);
        
        $manager->resetSequence('order', 1);
        
        $sequence->refresh();
        $this->assertEquals(1, $sequence->current_number);
    }

    public function test_serial_number_format_with_padding()
    {
        $manager = app(SerialManager::class);
        
        $serial = $manager->generate('invoice');
        
        // Should contain 5-digit padded number
        $this->assertMatchesRegularExpression('/\d{5}/', $serial);
    }

    public function test_prevents_duplicate_serials()
    {
        $this->expectException(\Azahari\SerialPattern\Exceptions\SerialCollisionException::class);
        
        config(['serial-pattern.logging.enabled' => true]);
        
        $manager = app(SerialManager::class);
        $serial = $manager->generate('invoice');
        
        // Manually create a duplicate
        SerialLog::create([
            'serial' => $serial,
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        // Try to generate with same conditions (force collision)
        $manager->generate('invoice');
    }
}
