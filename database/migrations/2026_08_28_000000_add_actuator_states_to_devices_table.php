<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('actuator_cooling_fan')->default(false)->after('free_heap');
            $table->boolean('actuator_nutrient_pump_a')->default(false)->after('actuator_cooling_fan');
            $table->boolean('actuator_nutrient_pump_b')->default(false)->after('actuator_nutrient_pump_a');
            $table->boolean('actuator_ph_up_pump')->default(false)->after('actuator_nutrient_pump_b');
            $table->boolean('actuator_ph_down_pump')->default(false)->after('actuator_ph_up_pump');
            $table->timestamp('actuator_states_updated_at')->nullable()->after('actuator_ph_down_pump');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'actuator_cooling_fan',
                'actuator_nutrient_pump_a',
                'actuator_nutrient_pump_b',
                'actuator_ph_up_pump',
                'actuator_ph_down_pump',
                'actuator_states_updated_at',
            ]);
        });
    }
};
