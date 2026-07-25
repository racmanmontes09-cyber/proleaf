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
        Schema::create('telemetries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->float('air_temperature')->nullable();
            $table->float('humidity')->nullable();
            $table->float('water_temperature')->nullable();
            $table->float('ph')->nullable();
            $table->float('ec')->nullable();
            $table->float('water_flow')->nullable();
            $table->float('water_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemetries');
    }
};
