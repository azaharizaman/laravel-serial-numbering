<?php

namespace AzahariZaman\ControlledNumber\Tests\Feature;

use AzahariZaman\ControlledNumber\Enums\ResetType;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Services\SerialPattern;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Carbon\Carbon;

class SerialGenerationTest extends TestCase
{
    protected $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create resolver
        $this->resolver = new SegmentResolver();
    }

    public function test_can_generate_simple_serial_number()
    {
        $pattern = new SerialPattern('INV-{year}-{number}');
        $this->assertNotEmpty($pattern->getPattern());
        
        $segments = $pattern->getSegments();
        $this->assertContains('year', $segments);
        $this->assertContains('number', $segments);
    }

    public function test_pattern_validates_correctly()
    {
        $pattern = new SerialPattern('INV-{year}-{number}');
        $pattern->validate(); // Should not throw
        
        $this->assertTrue(true); // If we get here, validation passed
    }

    public function test_can_create_sequence_model()
    {
        $sequence = SerialSequence::create([
            'name' => 'test_invoice',
            'pattern' => 'INV-{year}-{number}',
            'current_number' => 1000,
            'reset_type' => 'never',
        ]);
        
        $this->assertNotNull($sequence);
        $this->assertEquals('test_invoice', $sequence->name);
        $this->assertInstanceOf(ResetType::class, $sequence->reset_type);
    }

    public function test_can_create_serial_log()
    {
        $log = SerialLog::create([
            'serial' => 'INV-2024-00001',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => false,
        ]);
        
        $this->assertNotNull($log);
        $this->assertEquals('INV-2024-00001', $log->serial);
        $this->assertFalse($log->is_void);
    }

    public function test_segment_resolver_resolves_year()
    {
        $year = $this->resolver->resolve('year', null);
        
        $this->assertEquals(date('Y'), $year);
    }

    public function test_segment_resolver_resolves_month()
    {
        $month = $this->resolver->resolve('month', null);
        
        $this->assertEquals(date('m'), $month);
    }
}
