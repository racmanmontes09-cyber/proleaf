<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Telemetry;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelemetryUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_creation_and_attributes_casting(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-TEST-01',
            'name' => 'Test Device',
        ]);

        $measuredAt = Carbon::parse('2026-07-25 10:15:00');
        $receivedAt = Carbon::parse('2026-07-25 10:15:02');

        $telemetry = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 27.5,
            'humidity' => 65.0,
            'water_temperature' => 23.1,
            'ph' => 6.8,
            'ec' => 1.7,
            'water_flow' => 3.2,
            'water_level' => 90.0,
            'measured_at' => $measuredAt,
            'received_at' => $receivedAt,
            'sequence_number' => 101,
            'firmware_version' => '1.0.0',
            'signal_strength' => -72,
            'battery_voltage' => 4.15,
            'payload_version' => 1,
        ]);

        $this->assertDatabaseHas('telemetries', [
            'id' => $telemetry->id,
            'device_id' => $device->id,
            'sequence_number' => 101,
            'firmware_version' => '1.0.0',
            'signal_strength' => -72,
            'battery_voltage' => 4.15,
            'payload_version' => 1,
        ]);

        $this->assertInstanceOf(Carbon::class, $telemetry->measured_at);
        $this->assertInstanceOf(Carbon::class, $telemetry->received_at);
        $this->assertIsFloat($telemetry->air_temperature);
        $this->assertIsFloat($telemetry->battery_voltage);
        $this->assertIsInt($telemetry->sequence_number);
        $this->assertIsInt($telemetry->signal_strength);
    }

    public function test_telemetry_timestamp_auto_population_when_omitted(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-TEST-02',
            'name' => 'Test Device 2',
        ]);

        $now = Carbon::parse('2026-07-25 14:00:00');
        Carbon::setTestNow($now);

        $telemetry = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 28.0,
        ]);

        $this->assertEquals('2026-07-25 14:00:00', $telemetry->measured_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-07-25 14:00:00', $telemetry->received_at->format('Y-m-d H:i:s'));
        $this->assertEquals(1, $telemetry->payload_version);

        Carbon::setTestNow();
    }

    public function test_telemetry_belongs_to_device_relationship(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-TEST-03',
            'name' => 'Test Device 3',
        ]);

        $telemetry = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 22.0,
        ]);

        $this->assertTrue($telemetry->device->is($device));
    }

    public function test_device_has_many_telemetries_and_latest_telemetry_relationship(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-TEST-04',
            'name' => 'Test Device 4',
        ]);

        $first = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 20.0,
            'measured_at' => now()->subHours(2),
        ]);

        $second = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 25.0,
            'measured_at' => now()->subMinutes(10),
        ]);

        $this->assertCount(2, $device->telemetries);
        $this->assertTrue($device->latestTelemetry->is($second));
    }

    public function test_telemetry_duplicate_prevention_via_unique_index(): void
    {
        $device = Device::create([
            'device_id' => 'ESP-TEST-05',
            'name' => 'Test Device 5',
        ]);

        $measuredAt = '2026-07-25 11:00:00';

        Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 24.0,
            'measured_at' => $measuredAt,
        ]);

        $this->expectException(QueryException::class);

        Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 24.5,
            'measured_at' => $measuredAt,
        ]);
    }

    public function test_query_scopes_for_device_and_latest_reading(): void
    {
        $device1 = Device::create(['device_id' => 'ESP-DEV-01', 'name' => 'Device 1']);
        $device2 = Device::create(['device_id' => 'ESP-DEV-02', 'name' => 'Device 2']);

        $t1 = Telemetry::create([
            'device_id' => $device1->id,
            'air_temperature' => 21.0,
            'measured_at' => now()->subHours(3),
        ]);

        $t2 = Telemetry::create([
            'device_id' => $device1->id,
            'air_temperature' => 24.0,
            'measured_at' => now()->subHour(),
        ]);

        Telemetry::create([
            'device_id' => $device2->id,
            'air_temperature' => 30.0,
            'measured_at' => now(),
        ]);

        $device1Readings = Telemetry::forDevice($device1)->latestReading()->get();
        $this->assertCount(2, $device1Readings);
        $this->assertTrue($device1Readings->first()->is($t2));

        $latestMeasurement = Telemetry::latestMeasurement()->first();
        $this->assertNotNull($latestMeasurement);

        $latestForDev1 = Telemetry::latestForDevice($device1)->first();
        $this->assertTrue($latestForDev1->is($t2));

        $recentDevice1 = Telemetry::forDevice($device1)->recent(1)->get();
        $this->assertCount(1, $recentDevice1);
        $this->assertTrue($recentDevice1->first()->is($t2));
    }

    public function test_query_scopes_time_filtering(): void
    {
        $device = Device::create(['device_id' => 'ESP-TIME-01', 'name' => 'Time Test Device']);

        $now = Carbon::parse('2026-07-25 12:00:00');
        Carbon::setTestNow($now);

        $tOld = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 18.0,
            'measured_at' => $now->copy()->subDays(40),
        ]);

        $tWeekOld = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 20.0,
            'measured_at' => $now->copy()->subDays(5),
        ]);

        $tToday = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 25.0,
            'measured_at' => $now->copy()->subHours(2),
        ]);

        $this->assertCount(1, Telemetry::today()->get());
        $this->assertTrue(Telemetry::today()->first()->is($tToday));

        $this->assertCount(2, Telemetry::last7Days()->get());
        $this->assertCount(2, Telemetry::last30Days()->get());

        $report = Telemetry::betweenDates($now->copy()->subDays(10), $now)->get();
        $this->assertCount(2, $report);

        Carbon::setTestNow();
    }

    public function test_telemetry_accessors(): void
    {
        $device = Device::create(['device_id' => 'ESP-ACC-01', 'name' => 'Accessor Device']);

        $normalBattery = Telemetry::create([
            'device_id' => $device->id,
            'battery_voltage' => 3.7,
            'signal_strength' => -55,
            'measured_at' => now()->subMinutes(10),
        ]);

        $lowBattery = Telemetry::create([
            'device_id' => $device->id,
            'battery_voltage' => 3.1,
            'signal_strength' => -95,
            'measured_at' => now(),
        ]);

        $this->assertFalse($normalBattery->is_battery_low);
        $this->assertEquals('Excellent', $normalBattery->signal_quality);

        $this->assertTrue($lowBattery->is_battery_low);
        $this->assertEquals('Weak', $lowBattery->signal_quality);
    }

    public function test_telemetry_indexes_exist_in_database(): void
    {
        $this->assertTrue(Schema::hasColumn('telemetries', 'measured_at'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'received_at'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'sequence_number'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'firmware_version'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'signal_strength'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'battery_voltage'));
        $this->assertTrue(Schema::hasColumn('telemetries', 'payload_version'));
    }
}
