<?php

namespace AzahariZaman\ControlledNumber\Tests\Support;

class FakeSerialManager
{
    public string $generateSerial = 'GEN-0001';
    public string $previewSerial = 'PRE-0001';
    public array $generatedPayloads = [];
    public array $previewPayloads = [];
    public array $voidPayloads = [];

    public function generate(string $patternName, $model = null, array $context = []): string
    {
        $this->generatedPayloads[] = [$patternName, $model, $context];
        return $this->generateSerial;
    }

    public function preview(string $patternName, $model = null, array $context = []): string
    {
        $this->previewPayloads[] = [$patternName, $model, $context];
        return $this->previewSerial;
    }

    public function void(string $serial, ?string $reason = null): bool
    {
        $this->voidPayloads[] = [$serial, $reason];
        return $serial === 'GEN-0001';
    }
}
