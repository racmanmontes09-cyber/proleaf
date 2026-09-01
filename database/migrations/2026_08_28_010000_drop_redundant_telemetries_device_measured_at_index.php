<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telemetries', function (Blueprint $table) {
            $table->dropIndex('telemetries_device_measured_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('telemetries', function (Blueprint $table) {
            $table->index(['device_id', 'measured_at'], 'telemetries_device_measured_at_index');
        });
    }
};
