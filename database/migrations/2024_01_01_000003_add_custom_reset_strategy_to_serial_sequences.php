<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('serial_sequences', function (Blueprint $table) {
            $table->string('reset_strategy_class')->nullable()->after('reset_interval');
            $table->json('reset_strategy_config')->nullable()->after('reset_strategy_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_sequences', function (Blueprint $table) {
            $table->dropColumn(['reset_strategy_class', 'reset_strategy_config']);
        });
    }
};
