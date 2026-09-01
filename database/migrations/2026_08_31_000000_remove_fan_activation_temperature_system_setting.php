<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_settings')) {
            SystemSetting::query()
                ->where('key', 'fan_activation_temperature')
                ->delete();
        }
    }

    public function down(): void
    {
    }
};
