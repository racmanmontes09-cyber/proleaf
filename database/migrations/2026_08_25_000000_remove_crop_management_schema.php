<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('harvests');

        if (Schema::hasTable('permissions')) {
            Permission::query()
                ->whereIn('slug', [
                    'telemetry.export',
                    'reports.view',
                    'reports.export',
                    'harvest.view',
                    'harvest.create',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
    }
};