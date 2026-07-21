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
       Schema::create('devices', function (Blueprint $table) {
            $table->id();

            // Device Identification
            $table->string('device_id')->unique();
            $table->string('name')->nullable();

            // Firmware Information
            $table->string('firmware_version')->nullable();

            // Network Information
            $table->string('local_ip_address')->nullable();
            $table->integer('wifi_rssi')->nullable();

            // System Information
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->unsignedInteger('free_heap')->nullable();

            // Monitoring
            $table->timestamp('last_boot_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
