<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_forbidden_without_permission(): void
    {
        $user = User::factory()->create();
        // Give view permission so component can mount, but not update permission
        $role = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
        $viewPerm = Permission::firstOrCreate(['slug' => 'settings.view'], ['name' => 'Settings View']);
        $role->permissions()->syncWithoutDetaching([$viewPerm->id]);
        $user->assignRole($role);

        $this->actingAs($user);

        // Attempt to mount and save settings via Livewire directly by class
        Livewire::actingAs($user)->test(\App\Livewire\SettingsPage::class)
            ->call('save')
            ->assertSee('Forbidden.');
    }

    public function test_settings_page_allowed_with_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager']);
        $viewPerm = Permission::firstOrCreate(['slug' => 'settings.view'], ['name' => 'Settings View']);
        $updatePerm = Permission::firstOrCreate(['slug' => 'settings.update'], ['name' => 'Settings Update']);
        $role->permissions()->syncWithoutDetaching([$viewPerm->id, $updatePerm->id]);
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::actingAs($user)->test(\App\Livewire\SettingsPage::class)
            ->call('save')
            ->assertSet('saved', true)
            ->assertDontSee('Automatic Dosing')
            ->assertDontSee('limits for humidity')
            ->assertDontSee('Fan Activation Temperature')
            ->assertSee('Air Temperature');
    }
}
