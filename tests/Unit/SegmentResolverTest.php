<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Contracts\SegmentInterface;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Tests\Support\DummyModel;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Carbon\Carbon;

class SegmentResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_resolves_builtin_segments(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-03-10 14:15:30'));

        $resolver = new SegmentResolver();
        $segments = $resolver->resolveAll([
            'year',
            'year_short',
            'month',
            'month_name',
            'day',
            'hour',
            'minute',
            'second',
            'week',
            'quarter',
            'timestamp',
            'number',
        ], null, ['number' => '0007']);

        $this->assertSame('2025', $segments['year']);
        $this->assertSame('25', $segments['year_short']);
        $this->assertSame('03', $segments['month']);
        $this->assertSame('Mar', $segments['month_name']);
        $this->assertSame('10', $segments['day']);
        $this->assertSame('14', $segments['hour']);
        $this->assertSame('15', $segments['minute']);
        $this->assertSame('30', $segments['second']);
        $this->assertSame('11', $segments['week']);
        $this->assertSame('1', $segments['quarter']);
        $this->assertSame(Carbon::parse('2025-03-10 14:15:30')->timestamp, (int) $segments['timestamp']);
        $this->assertSame('0007', $segments['number']);
    }

    public function test_resolves_model_properties_without_cache(): void
    {
        $resolver = new SegmentResolver();
        $resolver->setCaching(false);

        $model = new DummyModel();
        $model->setAttribute('id', 42);
        $model->setAttribute('department', (object) ['code' => 'FIN']);
        $model->setAttribute('meta', ['region' => 'APAC']);

        $this->assertSame('FIN', $resolver->resolve('department.code', $model));
        $this->assertSame('APAC', $resolver->resolve('meta.region', $model));
        $this->assertSame('', $resolver->resolve('missing.property', $model));
    }

    public function test_resolves_custom_segment_via_interface(): void
    {
        $resolver = new SegmentResolver();
        $resolver->registerResolver('custom.segment', CustomSegmentResolver::class);

        $value = $resolver->resolve('custom.segment', null, ['foo' => 'bar']);

        $this->assertSame('custom:bar', $value);
    }

    public function test_returns_original_segment_when_unhandled(): void
    {
        $resolver = new SegmentResolver();
        $this->assertSame('unknown', $resolver->resolve('unknown'));
    }
}

class CustomSegmentResolver implements SegmentInterface
{
    public function resolve($model = null, array $context = []): string
    {
        return 'custom:' . ($context['foo'] ?? 'default');
    }

    public function getName(): string
    {
        return 'custom.segment';
    }

    public function validate(): bool
    {
        return true;
    }
}
