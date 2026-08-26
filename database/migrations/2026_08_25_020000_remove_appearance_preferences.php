<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\UserPreference;

return new class extends Migration
{
    public function up(): void
    {
        UserPreference::query()->each(function (UserPreference $preference): void {
            $dashboard = $preference->dashboard ?? [];
            unset($dashboard['appearance']);
            $preference->update(['dashboard' => $dashboard]);
        });
    }

    public function down(): void
    {
    }
};