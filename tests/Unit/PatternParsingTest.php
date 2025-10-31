<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Exceptions\InvalidPatternException;
use AzahariZaman\ControlledNumber\Services\SerialPattern;
use AzahariZaman\ControlledNumber\Tests\TestCase;

class PatternParsingTest extends TestCase
{
    public function test_can_parse_simple_pattern()
    {
        $pattern = new SerialPattern('INV-{year}-{number}');
        
        $segments = $pattern->getSegments();
        
        $this->assertCount(2, $segments);
        $this->assertContains('year', $segments);
        $this->assertContains('number', $segments);
    }

    public function test_can_parse_complex_pattern()
    {
        $pattern = new SerialPattern('{year}-{month}-{department.code}-{number}');
        
        $segments = $pattern->getSegments();
        
        $this->assertCount(4, $segments);
        $this->assertContains('year', $segments);
        $this->assertContains('month', $segments);
        $this->assertContains('department.code', $segments);
        $this->assertContains('number', $segments);
    }

    public function test_validates_empty_pattern()
    {
        $this->expectException(InvalidPatternException::class);
        
        $pattern = new SerialPattern('');
        $pattern->validate();
    }

    public function test_validates_pattern_without_number()
    {
        $this->expectException(InvalidPatternException::class);
        
        $pattern = new SerialPattern('INV-{year}-{month}');
        $pattern->validate();
    }

    public function test_validates_pattern_without_segments()
    {
        $this->expectException(InvalidPatternException::class);
        
        $pattern = new SerialPattern('INVOICE-STATIC');
        $pattern->validate();
    }

    public function test_can_build_serial_from_resolved_segments()
    {
        $pattern = new SerialPattern('INV-{year}-{number}');
        
        $serial = $pattern->build([
            'year' => '2024',
            'number' => '00001',
        ]);
        
        $this->assertEquals('INV-2024-00001', $serial);
    }

    public function test_formats_number_with_padding()
    {
        $pattern = new SerialPattern('{number}', ['digits' => 6]);
        
        $formatted = $pattern->formatNumber(42);
        
        $this->assertEquals('000042', $formatted);
    }

    public function test_detects_model_segments()
    {
        $pattern = new SerialPattern('{year}-{department.code}-{user.name}-{number}');
        
        $modelSegments = $pattern->getModelSegments();
        
        $this->assertCount(2, $modelSegments);
        $this->assertContains('department.code', $modelSegments);
        $this->assertContains('user.name', $modelSegments);
    }

    public function test_detects_datetime_segments()
    {
        $pattern = new SerialPattern('{year}-{month}-{day}-{number}');
        
        $dateTimeSegments = $pattern->getDateTimeSegments();
        
        $this->assertCount(3, $dateTimeSegments);
        $this->assertContains('year', $dateTimeSegments);
        $this->assertContains('month', $dateTimeSegments);
        $this->assertContains('day', $dateTimeSegments);
    }

    public function test_checks_if_pattern_has_segment()
    {
        $pattern = new SerialPattern('INV-{year}-{number}');
        
        $this->assertTrue($pattern->hasSegment('year'));
        $this->assertTrue($pattern->hasSegment('number'));
        $this->assertFalse($pattern->hasSegment('month'));
    }

    public function test_gets_number_config()
    {
        $pattern = new SerialPattern('{number}', [
            'start' => 1000,
            'digits' => 5,
        ]);
        
        $config = $pattern->getNumberConfig();
        
        $this->assertEquals(1000, $config['start']);
        $this->assertEquals(5, $config['digits']);
    }

    public function test_validates_invalid_segment_names()
    {
        $this->expectException(InvalidPatternException::class);
        
        $pattern = new SerialPattern('INV-{invalid@segment}-{number}');
        $pattern->validate();
    }
}
