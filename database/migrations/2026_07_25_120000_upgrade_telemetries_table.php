<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('telemetries', function (Blueprint $table) {
            $table->dateTime('measured_at', 6)->nullable()->after('device_id');
            $table->dateTime('received_at', 6)->nullable()->after('measured_at');
            $table->unsignedBigInteger('sequence_number')->nullable()->after('received_at');
            $table->string('firmware_version', 50)->nullable()->after('sequence_number');
            $table->integer('signal_strength')->nullable()->after('firmware_version');
            $table->decimal('battery_voltage', 5, 2)->nullable()->after('signal_strength');
            $table->unsignedSmallInteger('payload_version')->default(1)->after('battery_voltage');
        });

        // Backfill existing telemetry records with created_at timestamp
        DB::table('telemetries')->whereNull('measured_at')->update([
            'measured_at' => DB::raw('created_at'),
            'received_at' => DB::raw('created_at'),
            'payload_version' => 1,
        ]);

        // Ensure no duplicate (device_id, measured_at) combinations exist before adding unique index
        $duplicates = DB::table('telemetries')
            ->select('device_id', 'measured_at', DB::raw('COUNT(*) as count'))
            ->groupBy('device_id', 'measured_at')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('telemetries')
                ->where('device_id', $duplicate->device_id)
                ->where('measured_at', $duplicate->measured_at)
                ->orderBy('id')
                ->get();

            $offset = 0;
            foreach ($rows as $row) {
                if ($offset > 0) {
                    $newTime = \Illuminate\Support\Carbon::parse($row->measured_at)->addSeconds($offset);
                    DB::table('telemetries')->where('id', $row->id)->update([
                        'measured_at' => $newTime,
                    ]);
                }
                $offset++;
            }
        }

        Schema::table('telemetries', function (Blueprint $table) {
            $table->unique(['device_id', 'measured_at'], 'telemetries_device_id_measured_at_unique');
            $table->index('measured_at', 'telemetries_measured_at_index');
            $table->index(['device_id', 'created_at'], 'telemetries_device_id_created_at_index');
            $table->index(['device_id', 'sequence_number'], 'telemetries_device_id_sequence_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telemetries', function (Blueprint $table) {
            $table->index('device_id', 'telemetries_device_id_fallback_idx');
        });

        Schema::table('telemetries', function (Blueprint $table) {
            $table->dropUnique('telemetries_device_id_measured_at_unique');
            $table->dropIndex('telemetries_measured_at_index');
            $table->dropIndex('telemetries_device_id_created_at_index');
            $table->dropIndex('telemetries_device_id_sequence_number_index');

            $table->dropColumn([
                'measured_at',
                'received_at',
                'sequence_number',
                'firmware_version',
                'signal_strength',
                'battery_voltage',
                'payload_version',
            ]);
        });
    }
};
