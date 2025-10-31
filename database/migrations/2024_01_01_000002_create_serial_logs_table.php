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
        Schema::create('serial_logs', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique()->index();
            $table->string('pattern_name')->index();
            $table->nullableMorphs('model');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->boolean('is_void')->default(false)->index();
            $table->timestamps();

            $table->index(['pattern_name', 'is_void']);
            $table->index(['user_id', 'generated_at']);
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_logs');
    }
};
