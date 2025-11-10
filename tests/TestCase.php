<?php

namespace AzahariZaman\ControlledNumber\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;
use AzahariZaman\ControlledNumber\Tests\Support\TestApp;
use AzahariZaman\ControlledNumber\Tests\Support\TestConfig;
use AzahariZaman\ControlledNumber\Tests\Support\TestAuth;
use AzahariZaman\ControlledNumber\Tests\Support\TestEvents;

abstract class TestCase extends BaseTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpDatabase();
        $this->runMigrations();
        
        // Set default config after database is ready
        if (function_exists('config')) {
            config([
                'serial-pattern.lock.enabled' => false, // Disable locking for tests
                'serial-pattern.logging.enabled' => true,
                'serial-pattern.logging.track_user' => false,
                'serial-pattern.logging.activity_log.enabled' => false, // Disable Spatie activity log in tests
            ]);
        }
    }

    protected function setUpDatabase(): void
    {
        $this->db = new DB;
        
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        
        $this->db->setEventDispatcher(new Dispatcher(new Container));
        $this->db->setAsGlobal();
        $this->db->bootEloquent();
    }

    protected function runMigrations(): void
    {
        DB::schema()->create('serial_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pattern');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->string('reset_type')->default('never');
            $table->unsignedInteger('reset_interval')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->string('reset_strategy_class')->nullable();
            $table->json('reset_strategy_config')->nullable();
            $table->timestamps();
            
            $table->unique('name');
        });

        DB::schema()->create('serial_logs', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            $table->string('pattern_name');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->boolean('is_void')->default(false);
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
        });
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            DB::schema()->dropIfExists('serial_logs');
            DB::schema()->dropIfExists('serial_sequences');
        }

        TestApp::flush();
        TestConfig::reset();
        TestAuth::guest();
        TestEvents::reset();
        
        parent::tearDown();
    }
}