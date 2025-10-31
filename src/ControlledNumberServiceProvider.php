<?php

namespace AzahariZaman\ControlledNumber;

use Illuminate\Support\ServiceProvider;
use AzahariZaman\ControlledNumber\Models\SerialLog;
use AzahariZaman\ControlledNumber\Services\SerialManager;
use AzahariZaman\ControlledNumber\Services\SegmentResolver;
use AzahariZaman\ControlledNumber\Observers\SerialLogObserver;
use AzahariZaman\ControlledNumber\Console\Commands\ValidatePatternsCommand;

class ControlledNumberServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/serial-pattern.php',
            'serial-pattern'
        );

        $this->app->singleton(SegmentResolver::class, function ($app) {
            return new SegmentResolver();
        });

        $this->app->singleton(SerialManager::class, function ($app) {
            return new SerialManager($app->make(SegmentResolver::class));
        });

        $this->app->alias(SerialManager::class, 'serial-manager');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerObservers();
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishables(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/serial-pattern.php' => config_path('serial-pattern.php'),
            ], 'serial-pattern-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'serial-pattern-migrations');
        }
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidatePatternsCommand::class,
            ]);
        }
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        SerialLog::observe(SerialLogObserver::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            SerialManager::class,
            SegmentResolver::class,
            'serial-manager',
        ];
    }
}
