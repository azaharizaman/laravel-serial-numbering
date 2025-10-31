<?php

namespace Azahari\SerialPattern\Tests\Unit;

use Azahari\SerialPattern\Exceptions\SerialDeletionNotAllowedException;
use Azahari\SerialPattern\Models\SerialLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class LoggingTest extends TestCase
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
    }

    public function test_can_create_serial_log()
    {
        $log = SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        $this->assertNotNull($log);
        $this->assertEquals('INV-2024-00001', $log->serial);
        $this->assertFalse($log->is_void);
    }

    public function test_can_void_serial_log()
    {
        $log = SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        $log->void('Testing void functionality');
        
        $this->assertTrue($log->is_void);
        $this->assertEquals('Testing void functionality', $log->void_reason);
        $this->assertNotNull($log->voided_at);
    }

    public function test_cannot_delete_serial_log()
    {
        $this->expectException(SerialDeletionNotAllowedException::class);
        
        $log = SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        $log->delete();
    }

    public function test_active_scope_filters_voided_logs()
    {
        SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        SerialLog::create([
            'serial' => 'INV-2024-00002',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => true,
        ]);
        
        $activeLogs = SerialLog::active()->get();
        
        $this->assertCount(1, $activeLogs);
        $this->assertEquals('INV-2024-00001', $activeLogs->first()->serial);
    }

    public function test_voided_scope_filters_active_logs()
    {
        SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        SerialLog::create([
            'serial' => 'INV-2024-00002',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => true,
        ]);
        
        $voidedLogs = SerialLog::voided()->get();
        
        $this->assertCount(1, $voidedLogs);
        $this->assertEquals('INV-2024-00002', $voidedLogs->first()->serial);
    }

    public function test_for_pattern_scope()
    {
        SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        SerialLog::create([
            'serial' => 'ORD-2024-00001',
            'pattern_name' => 'order',
            'generated_at' => now(),
            'is_void' => false,
        ]);
        
        $invoiceLogs = SerialLog::forPattern('invoice')->get();
        
        $this->assertCount(1, $invoiceLogs);
        $this->assertEquals('invoice', $invoiceLogs->first()->pattern_name);
    }
}
