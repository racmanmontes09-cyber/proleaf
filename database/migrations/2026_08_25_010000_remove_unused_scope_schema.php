<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\SystemSetting;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('greenhouses') && Schema::hasColumn('greenhouses', 'description')) {
            Schema::table('greenhouses', function (Blueprint $table): void {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('permissions')) {
            Permission::query()->where('slug', 'ota.deploy')->delete();
        }

        if (Schema::hasTable('system_settings')) {
            SystemSetting::query()->whereIn('key', [
                'dashboard_notifications',
                'browser_notifications',
                'alert_cooldown',
                'critical_alert_repeat',
                'enable_notifications',
                'future_ota_enabled',
            ])->delete();
        }
    }

    public function down(): void
    {
    }
};