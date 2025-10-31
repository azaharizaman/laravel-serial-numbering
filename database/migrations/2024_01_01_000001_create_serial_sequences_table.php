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
        Schema::create('serial_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->index();
            $table->string('pattern');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->string('reset_type')->default('never');
            $table->unsignedInteger('reset_interval')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            $table->index(['name', 'reset_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_sequences');
    }
};
