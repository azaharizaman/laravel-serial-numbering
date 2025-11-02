<?php

namespace AzahariZaman\ControlledNumber\Tests\Unit;

use AzahariZaman\ControlledNumber\Helpers\SerialHelper;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Tests\Support\FakeSerialManager;
use AzahariZaman\ControlledNumber\Tests\Support\TestApp;
use AzahariZaman\ControlledNumber\Tests\TestCase;
use Carbon\Carbon;

class SerialHelperTest extends TestCase
{
    public function test_format_number_pads_with_zeroes(): void
    {
        $this->assertSame('0005', SerialHelper::formatNumber(5));
        $this->assertSame('000123', SerialHelper::formatNumber(123, 6));
    }

    public function test_validate_pattern_reports_errors(): void
    {
        $result = SerialHelper::validatePattern('INVOICE');

        $this->assertFalse($result['valid']);
        $this->assertContains('Pattern must contain at least one segment (e.g., {year}, {number})', $result['errors']);
        $this->assertContains('Pattern must contain {number} segment', $result['errors']);
    }

    public function test_validate_pattern_success(): void
    {
        $result = SerialHelper::validatePattern('INV-{year}-{number}');

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_exists_and_is_active_helpers(): void
    {
        SerialLog::create([
            'serial' => 'INV-2025-00001',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => false,
        ]);

        SerialLog::create([
            'serial' => 'INV-2025-00002',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => true,
        ]);

        $this->assertTrue(SerialHelper::exists('INV-2025-00001'));
        $this->assertTrue(SerialHelper::exists('INV-2025-00002'));
        $this->assertTrue(SerialHelper::isActive('INV-2025-00001'));
        $this->assertFalse(SerialHelper::isActive('INV-2025-00002'));
        $this->assertNull(SerialHelper::getLog('UNKNOWN'));
        $this->assertSame('INV-2025-00001', SerialHelper::getLog('INV-2025-00001')->serial);
    }

    public function test_generate_preview_and_void_delegate_to_serial_manager(): void
    {
        $fakeManager = new FakeSerialManager();
        TestApp::bind(SerialManager::class, $fakeManager);

        $serial = SerialHelper::generate('invoice', ['id' => 5], ['key' => 'value']);
        $preview = SerialHelper::preview('invoice');
        $voidSuccess = SerialHelper::void('GEN-0001', 'duplicate');
        $voidFailure = SerialHelper::void('UNKNOWN');

        $this->assertSame('GEN-0001', $serial);
        $this->assertSame('PRE-0001', $preview);
        $this->assertTrue($voidSuccess);
        $this->assertFalse($voidFailure);
        $this->assertCount(1, $fakeManager->generatedPayloads);
        $this->assertCount(1, $fakeManager->previewPayloads);
        $this->assertCount(2, $fakeManager->voidPayloads);
        $this->assertSame(['invoice', ['id' => 5], ['key' => 'value']], $fakeManager->generatedPayloads[0]);
    }

    public function test_export_logs_supports_filters(): void
    {
        SerialLog::create([
            'serial' => 'INV-1',
            'pattern_name' => 'invoice',
            'user_id' => 1,
            'generated_at' => Carbon::parse('2025-01-10 10:00:00'),
            'is_void' => false,
        ]);

        SerialLog::create([
            'serial' => 'INV-2',
            'pattern_name' => 'invoice',
            'user_id' => 2,
            'generated_at' => Carbon::parse('2025-02-10 10:00:00'),
            'is_void' => true,
            'voided_at' => Carbon::parse('2025-02-11 09:00:00'),
            'void_reason' => 'Duplicate',
        ]);

        SerialLog::create([
            'serial' => 'ORD-1',
            'pattern_name' => 'order',
            'user_id' => 1,
            'generated_at' => Carbon::parse('2025-01-15 12:30:00'),
            'is_void' => false,
        ]);

        $collection = SerialHelper::exportLogs([
            'pattern' => 'invoice',
            'user_id' => 1,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
            'is_void' => false,
        ]);

        $this->assertCount(1, $collection);
        $this->assertSame('INV-1', $collection->first()->serial);
    }

    public function test_export_to_csv_outputs_formatted_rows(): void
    {
        SerialLog::create([
            'serial' => 'INV-100',
            'pattern_name' => 'invoice',
            'user_id' => 7,
            'generated_at' => Carbon::parse('2025-03-01 08:15:00'),
            'is_void' => false,
        ]);

        $csv = SerialHelper::exportToCsv(['pattern' => 'invoice']);

        $lines = explode("\n", trim($csv));
        $this->assertSame('"Serial","Pattern","Model Type","Model ID","User ID","Generated At","Voided At","Void Reason","Is Void"', $lines[0]);
        $this->assertStringContainsString('"INV-100"', $lines[1]);
        $this->assertStringContainsString('"invoice"', $lines[1]);
    }

    public function test_export_to_json_returns_pretty_json(): void
    {
        SerialLog::create([
            'serial' => 'ORD-200',
            'pattern_name' => 'order',
            'generated_at' => Carbon::parse('2025-04-05 09:00:00'),
            'is_void' => false,
        ]);

        $json = SerialHelper::exportToJson(['pattern' => 'order']);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('ORD-200', $decoded[0]['serial']);
    }

    public function test_get_pattern_stats_calculates_rates(): void
    {
        SerialLog::create([
            'serial' => 'INV-300',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => false,
        ]);

        SerialLog::create([
            'serial' => 'INV-301',
            'pattern_name' => 'invoice',
            'generated_at' => Carbon::now(),
            'is_void' => true,
        ]);

        $stats = SerialHelper::getPatternStats('invoice');

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['active']);
        $this->assertSame(1, $stats['voided']);
        $this->assertSame(50.0, $stats['void_rate']);
    }
}
