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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telemetry_id')->nullable()->constrained('telemetries')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('sensor');
            $table->string('severity', 20);
            $table->string('status', 20)->default('active');
            $table->decimal('value', 12, 4)->nullable();
            $table->string('threshold')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('severity');
            $table->index('status');
            $table->index('created_at');
            $table->index(['device_id', 'status']);
            $table->index(['device_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
