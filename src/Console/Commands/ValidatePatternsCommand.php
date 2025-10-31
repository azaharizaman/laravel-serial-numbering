<?php

namespace Azahari\SerialPattern\Console\Commands;

use Azahari\SerialPattern\Helpers\SerialHelper;
use Azahari\SerialPattern\Services\SerialManager;
use Illuminate\Console\Command;

class ValidatePatternsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serial:validate-patterns 
                            {--pattern= : Validate a specific pattern}
                            {--stats : Show statistics for patterns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate serial number patterns for uniqueness, reset safety, and segment integrity';

    /**
     * Execute the console command.
     */
    public function handle(SerialManager $manager): int
    {
        $patternName = $this->option('pattern');
        $showStats = $this->option('stats');

        if ($patternName) {
            return $this->validateSinglePattern($manager, $patternName, $showStats);
        }

        return $this->validateAllPatterns($manager, $showStats);
    }

    /**
     * Validate all patterns.
     */
    protected function validateAllPatterns(SerialManager $manager, bool $showStats): int
    {
        $patterns = $manager->getPatterns();

        if (empty($patterns)) {
            $this->warn('No patterns configured.');
            return self::SUCCESS;
        }

        $this->info('Validating ' . count($patterns) . ' pattern(s)...');
        $this->newLine();

        $hasErrors = false;

        foreach ($patterns as $name => $config) {
            $result = $this->validatePattern($name, $config, $showStats);
            if (!$result) {
                $hasErrors = true;
            }
        }

        $this->newLine();

        if ($hasErrors) {
            $this->error('❌ Some patterns have validation errors.');
            return self::FAILURE;
        }

        $this->info('✅ All patterns are valid!');
        return self::SUCCESS;
    }

    /**
     * Validate a single pattern.
     */
    protected function validateSinglePattern(SerialManager $manager, string $patternName, bool $showStats): int
    {
        if (!$manager->hasPattern($patternName)) {
            $this->error("Pattern '{$patternName}' not found.");
            return self::FAILURE;
        }

        $patterns = $manager->getPatterns();
        $config = $patterns[$patternName];

        $this->info("Validating pattern: {$patternName}");
        $this->newLine();

        $result = $this->validatePattern($patternName, $config, $showStats);

        $this->newLine();

        if (!$result) {
            $this->error("❌ Pattern '{$patternName}' has validation errors.");
            return self::FAILURE;
        }

        $this->info("✅ Pattern '{$patternName}' is valid!");
        return self::SUCCESS;
    }

    /**
     * Validate a single pattern and display results.
     */
    protected function validatePattern(string $name, array $config, bool $showStats): bool
    {
        $pattern = $config['pattern'] ?? '';
        
        $this->line("📋 <fg=cyan>Pattern:</> {$name}");
        $this->line("   <fg=gray>Definition:</> {$pattern}");

        // Validate pattern syntax
        $validation = SerialHelper::validatePattern($pattern);

        if (!$validation['valid']) {
            $this->line("   <fg=red>Status:</> ❌ Invalid");
            foreach ($validation['errors'] as $error) {
                $this->line("   <fg=red>  • {$error}</>");
            }
            $this->newLine();
            return false;
        }

        $this->line("   <fg=green>Status:</> ✅ Valid");

        // Display configuration
        $this->displayConfig($config);

        // Show statistics if requested
        if ($showStats) {
            $this->displayStats($name);
        }

        $this->newLine();
        return true;
    }

    /**
     * Display pattern configuration.
     */
    protected function displayConfig(array $config): void
    {
        $this->line("   <fg=gray>Configuration:</>");
        $this->line("     • Start: " . ($config['start'] ?? 1));
        $this->line("     • Digits: " . ($config['digits'] ?? 4));
        $this->line("     • Reset: " . ($config['reset'] ?? 'never'));
        
        if (isset($config['interval'])) {
            $this->line("     • Interval: " . $config['interval'] . " day(s)");
        }
    }

    /**
     * Display pattern statistics.
     */
    protected function displayStats(string $patternName): void
    {
        $stats = SerialHelper::getPatternStats($patternName);

        $this->line("   <fg=gray>Statistics:</>");
        $this->line("     • Total Generated: " . $stats['total']);
        $this->line("     • Active: <fg=green>" . $stats['active'] . "</>");
        $this->line("     • Voided: <fg=yellow>" . $stats['voided'] . "</>");
        $this->line("     • Void Rate: " . $stats['void_rate'] . "%");
    }
}
