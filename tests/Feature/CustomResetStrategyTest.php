<?php

namespace AzahariZaman\ControlledNumber\Tests\Feature;

use AzahariZaman\ControlledNumber\Enums\ResetType;
use AzahariZaman\ControlledNumber\Models\SerialSequence;
use AzahariZaman\ControlledNumber\Resets\FiscalYearReset;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Tests\TestCase;

class CustomResetStrategyTest extends TestCase
{
    protected SerialManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new SerialManager(new SegmentResolver());
    }

    /** @test */
    public function it_creates_sequence_with_custom_reset_strategy()
    {
        $this->markTestSkipped('Lock facade integration needs fixing in test environment');
        
        $this->manager->registerPattern('fiscal_test', [
            'pattern' => 'FY-{number}',
            'start' => 1,
            'digits' => 4,
            'reset' => 'custom',
            'reset_strategy' => FiscalYearReset::class,
            'reset_config' => [
                'start_month' => 4,
                'start_day' => 1,
            ],
        ]);

        $serial = $this->manager->generate('fiscal_test');

        $this->assertStringStartsWith('FY-', $serial);

        $sequence = SerialSequence::where('name', 'fiscal_test')->first();
        $this->assertNotNull($sequence);
        $this->assertEquals(ResetType::CUSTOM, $sequence->reset_type);
        $this->assertEquals(FiscalYearReset::class, $sequence->reset_strategy_class);
        $this->assertIsArray($sequence->reset_strategy_config);
        $this->assertEquals(4, $sequence->reset_strategy_config['start_month']);
    }

    /** @test */
    public function it_evaluates_custom_reset_strategy()
    {
        $sequence = SerialSequence::create([
            'name' => 'fiscal_seq',
            'pattern' => 'FY-{number}',
            'current_number' => 100,
            'reset_type' => ResetType::CUSTOM,
            'reset_interval' => 1,
            'last_reset_at' => new \DateTime('2023-05-01'), // Last fiscal year
            'reset_strategy_class' => FiscalYearReset::class,
            'reset_strategy_config' => [
                'start_month' => 4,
                'start_day' => 1,
            ],
        ]);

        // Should reset because we're in a new fiscal year
        $shouldReset = $sequence->shouldReset();
        $this->assertTrue($shouldReset);
    }

    /** @test */
    public function it_gets_reset_strategy_instance()
    {
        $sequence = SerialSequence::create([
            'name' => 'fiscal_seq',
            'pattern' => 'FY-{number}',
            'current_number' => 1,
            'reset_type' => ResetType::CUSTOM,
            'reset_interval' => 1,
            'last_reset_at' => new \DateTime(),
            'reset_strategy_class' => FiscalYearReset::class,
            'reset_strategy_config' => [
                'start_month' => 7,
                'start_day' => 1,
            ],
        ]);

        $strategy = $sequence->getResetStrategy();
        
        $this->assertInstanceOf(FiscalYearReset::class, $strategy);
        $this->assertStringContainsString('July 1', $strategy->description());
    }

    /** @test */
    public function it_returns_null_for_non_custom_reset_type()
    {
        $sequence = SerialSequence::create([
            'name' => 'monthly_seq',
            'pattern' => 'M-{number}',
            'current_number' => 1,
            'reset_type' => ResetType::MONTHLY,
            'reset_interval' => 1,
            'last_reset_at' => new \DateTime(),
        ]);

        $strategy = $sequence->getResetStrategy();
        
        $this->assertNull($strategy);
    }

    /** @test */
    public function it_handles_invalid_strategy_class()
    {
        $sequence = SerialSequence::create([
            'name' => 'invalid_seq',
            'pattern' => 'INV-{number}',
            'current_number' => 1,
            'reset_type' => ResetType::CUSTOM,
            'reset_interval' => 1,
            'last_reset_at' => new \DateTime(),
            'reset_strategy_class' => 'NonExistentClass',
            'reset_strategy_config' => [],
        ]);

        $shouldReset = $sequence->shouldReset();
        
        // Should return false for invalid class
        $this->assertFalse($shouldReset);

        $strategy = $sequence->getResetStrategy();
        $this->assertNull($strategy);
    }
}
