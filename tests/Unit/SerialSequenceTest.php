<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Models\SerialSequence;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Carbon\Carbon;

class SerialSequenceTest extends TestCase
{
    public function test_increment_number_increases_counter(): void
    {
        $sequence = SerialSequence::create([
            'name' => 'invoice',
            'pattern' => 'INV-{number}',
            'current_number' => 5,
            'reset_type' => 'never',
        ]);

        $newValue = $sequence->incrementNumber();
        $sequence->refresh();

        $this->assertSame(6, $newValue);
        $this->assertSame(6, $sequence->current_number);
    }

    public function test_reset_updates_current_number_and_timestamp(): void
    {
        $frozen = Carbon::parse('2025-05-01 10:00:00');
        Carbon::setTestNow($frozen);

        try {
            $sequence = SerialSequence::create([
                'name' => 'order',
                'pattern' => 'ORD-{number}',
                'current_number' => 20,
                'reset_type' => 'monthly',
            ]);

            $sequence->reset(3);
            $sequence->refresh();

            $this->assertSame(3, $sequence->current_number);
            $this->assertEquals('2025-05-01 10:00:00', $sequence->last_reset_at->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_should_reset_based_on_reset_type(): void
    {
        $sequence = SerialSequence::create([
            'name' => 'weekly',
            'pattern' => 'W-{number}',
            'current_number' => 1,
            'reset_type' => 'daily',
            'last_reset_at' => Carbon::parse('2025-01-01'),
        ]);

        $this->assertTrue($sequence->shouldReset());

        $current = new \DateTime();
        $sequence->update(['last_reset_at' => Carbon::instance($current)]);
        $this->assertFalse($sequence->fresh()->shouldReset());
    }
}
