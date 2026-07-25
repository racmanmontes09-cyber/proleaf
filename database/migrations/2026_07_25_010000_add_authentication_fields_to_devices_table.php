<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
            $table->string('status')->default('active')->after('name')->index();
            $table->string('device_token_hash', 64)->nullable()->after('status')->unique();
            $table->timestamp('device_token_expires_at')->nullable()->after('device_token_hash');
            $table->timestamp('device_token_last_used_at')->nullable()->after('device_token_expires_at');
            $table->timestamp('device_token_revoked_at')->nullable()->after('device_token_last_used_at');
        });

        DB::table('devices')
            ->whereNull('uuid')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($devices): void {
                foreach ($devices as $device) {
                    DB::table('devices')
                        ->where('id', $device->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'status',
                'device_token_hash',
                'device_token_expires_at',
                'device_token_last_used_at',
                'device_token_revoked_at',
            ]);
        });
    }
};
