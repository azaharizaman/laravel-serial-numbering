<?php

use AzahariZaman\ControlledNumber\Http\Controllers\SerialNumberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the SerialPatternServiceProvider and provide
| RESTful API endpoints for serial number operations.
|
*/

Route::prefix('api/v1/serial-numbers')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        
        // Generate a new serial number
        Route::post('generate', [SerialNumberController::class, 'generate'])
            ->name('serial.generate');
        
        // Preview next serial without generating
        Route::get('{type}/peek', [SerialNumberController::class, 'peek'])
            ->name('serial.peek');
        
        // Reset a sequence counter
        Route::post('{type}/reset', [SerialNumberController::class, 'reset'])
            ->name('serial.reset');
        
        // Void a serial number
        Route::post('{serial}/void', [SerialNumberController::class, 'void'])
            ->name('serial.void');
        
        // Query serial logs
        Route::get('logs', [SerialNumberController::class, 'logs'])
            ->name('serial.logs');
    });
