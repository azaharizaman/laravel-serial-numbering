<?php

namespace AzahariZaman\ControlledNumber\Tests\Stress;

use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ConcurrencyStressTest extends TestCase
{
    protected SerialManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip all stress tests in package test environment
        // These tests should be run in a full Laravel application
        $this->markTestSkipped('Stress tests require full Laravel environment with Cache facade');
        
        $this->manager = new SerialManager(new SegmentResolver());
    }

    /**
     * @test
     * @group stress
     */
    public function it_handles_100_concurrent_serial_generations_without_collision()
    {
        $this->manager->registerPattern('stress_test', [
            'pattern' => 'STR-{number}',
            'start' => 1,
            'digits' => 6,
            'reset' => 'never',
        ]);

        $serialNumbers = [];
        $iterations = 100;

        // Simulate concurrent requests using loop
        // (True parallel execution requires process forking which PHPUnit doesn't support well)
        for ($i = 0; $i < $iterations; $i++) {
            $serial = $this->manager->generate('stress_test');
            $serialNumbers[] = $serial;
        }

        // Verify all serials are unique
        $this->assertCount($iterations, $serialNumbers);
        $this->assertCount($iterations, array_unique($serialNumbers), 'All serial numbers should be unique');

        // Verify sequential numbering
        for ($i = 0; $i < $iterations; $i++) {
            $expectedNumber = str_pad((string)($i + 1), 6, '0', STR_PAD_LEFT);
            $this->assertEquals("STR-{$expectedNumber}", $serialNumbers[$i]);
        }
    }

    /**
     * @test
     * @group stress
     */
    public function it_handles_rapid_serial_generation_with_database_transactions()
    {
        $this->manager->registerPattern('transaction_test', [
            'pattern' => 'TXN-{number}',
            'start' => 1000,
            'digits' => 5,
            'reset' => 'never',
        ]);

        $count = 50;
        $serials = [];

        // Test rapid generation
        $startTime = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            $serials[] = $this->manager->generate('transaction_test');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Performance assertion: should complete in reasonable time
        $this->assertLessThan(10, $duration, 'Generation should complete within 10 seconds');

        // Verify uniqueness
        $this->assertCount($count, array_unique($serials));

        // Calculate throughput
        $throughput = $count / $duration;
        echo "\nThroughput: " . number_format($throughput, 2) . " serials/second\n";
        echo "Average time per serial: " . number_format(($duration / $count) * 1000, 2) . " ms\n";
    }

    /**
     * @test
     * @group stress
     */
    public function it_maintains_sequence_integrity_across_multiple_patterns()
    {
        // Register multiple patterns
        $patterns = [
            'invoice' => ['pattern' => 'INV-{number}', 'start' => 1, 'digits' => 4, 'reset' => 'never'],
            'order' => ['pattern' => 'ORD-{number}', 'start' => 100, 'digits' => 5, 'reset' => 'never'],
            'ticket' => ['pattern' => 'TKT-{number}', 'start' => 1000, 'digits' => 6, 'reset' => 'never'],
        ];

        foreach ($patterns as $name => $config) {
            $this->manager->registerPattern($name, $config);
        }

        $iterations = 30;
        $results = [];

        // Generate from all patterns concurrently
        for ($i = 0; $i < $iterations; $i++) {
            foreach (array_keys($patterns) as $patternName) {
                $serial = $this->manager->generate($patternName);
                $results[$patternName][] = $serial;
            }
        }

        // Verify each pattern maintained its own sequence
        foreach ($patterns as $name => $config) {
            $this->assertCount($iterations, $results[$name]);
            $this->assertCount($iterations, array_unique($results[$name]), "Pattern {$name} should have unique serials");
            
            // Verify sequential order
            $prefix = explode('-', $config['pattern'])[0];
            for ($i = 0; $i < $iterations; $i++) {
                $expectedNumber = str_pad((string)($config['start'] + $i), $config['digits'], '0', STR_PAD_LEFT);
                $this->assertEquals("{$prefix}-{$expectedNumber}", $results[$name][$i]);
            }
        }
    }

    /**
     * @test
     * @group stress
     */
    public function it_handles_database_rollback_scenarios()
    {
        $this->manager->registerPattern('rollback_test', [
            'pattern' => 'RBK-{number}',
            'start' => 1,
            'digits' => 4,
            'reset' => 'never',
        ]);

        // Generate some serials
        $serial1 = $this->manager->generate('rollback_test');
        $this->assertEquals('RBK-0001', $serial1);

        // Simulate transaction rollback
        try {
            DB::beginTransaction();
            $serial2 = $this->manager->generate('rollback_test');
            $this->assertEquals('RBK-0002', $serial2);
            
            // Force rollback
            throw new \Exception('Simulated error');
            
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Next serial should be 0003 (sequence counter doesn't rollback)
        $serial3 = $this->manager->generate('rollback_test');
        $this->assertEquals('RBK-0003', $serial3);
    }

    /**
     * @test
     * @group stress
     */
    public function it_measures_memory_usage_during_bulk_generation()
    {
        $this->manager->registerPattern('memory_test', [
            'pattern' => 'MEM-{number}',
            'start' => 1,
            'digits' => 6,
            'reset' => 'never',
        ]);

        $startMemory = memory_get_usage();
        $iterations = 100;

        for ($i = 0; $i < $iterations; $i++) {
            $this->manager->generate('memory_test');
        }

        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        echo "\nMemory used for {$iterations} generations: " . number_format($memoryUsed / 1024, 2) . " KB\n";
        echo "Average memory per generation: " . number_format($memoryUsed / $iterations, 2) . " bytes\n";

        // Assert reasonable memory usage (less than 10MB for 100 generations)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable');
    }

    /**
     * @test
     * @group stress
     */
    public function it_handles_pattern_with_dynamic_segments_under_load()
    {
        $this->manager->registerPattern('dynamic_stress', [
            'pattern' => '{year}-{month}-{day}-{number}',
            'start' => 1,
            'digits' => 4,
            'reset' => 'daily',
        ]);

        $count = 50;
        $serials = [];

        for ($i = 0; $i < $count; $i++) {
            $serials[] = $this->manager->generate('dynamic_stress');
        }

        // Verify all serials start with today's date
        $today = (new \DateTime())->format('Y-m-d');
        foreach ($serials as $serial) {
            $this->assertStringStartsWith($today, $serial);
        }

        // Verify uniqueness
        $this->assertCount($count, array_unique($serials));
    }

    /**
     * @test
     * @group stress
     * @group performance
     */
    public function it_benchmarks_generation_performance_across_different_pattern_complexities()
    {
        $patterns = [
            'simple' => ['pattern' => 'S-{number}', 'start' => 1, 'digits' => 4],
            'moderate' => ['pattern' => '{year}-{month}-{number}', 'start' => 1, 'digits' => 5],
            'complex' => ['pattern' => '{year}-Q{quarter}-{week}-{number}', 'start' => 1, 'digits' => 6],
        ];

        $iterations = 20;
        $benchmarks = [];

        foreach ($patterns as $name => $config) {
            $this->manager->registerPattern("benchmark_{$name}", array_merge($config, ['reset' => 'never']));
            
            $startTime = microtime(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                $this->manager->generate("benchmark_{$name}");
            }
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            $benchmarks[$name] = [
                'total_time' => $duration,
                'avg_time' => ($duration / $iterations) * 1000, // in milliseconds
                'throughput' => $iterations / $duration,
            ];
        }

        echo "\n=== Performance Benchmarks ===\n";
        foreach ($benchmarks as $name => $stats) {
            echo sprintf(
                "%s: %.2f ms/serial, %.2f serials/sec\n",
                ucfirst($name),
                $stats['avg_time'],
                $stats['throughput']
            );
        }

        // All patterns should complete within reasonable time
        foreach ($benchmarks as $name => $stats) {
            $this->assertLessThan(5, $stats['total_time'], "{$name} pattern should complete quickly");
        }
    }
}
