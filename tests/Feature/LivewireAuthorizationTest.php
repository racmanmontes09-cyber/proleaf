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
        $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $viewPerm = Permission::create(['name' => 'Settings View', 'slug' => 'settings.view']);
        $role->permissions()->attach($viewPerm->id);
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
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $viewPerm = Permission::create(['name' => 'Settings View', 'slug' => 'settings.view']);
        $updatePerm = Permission::create(['name' => 'Settings Update', 'slug' => 'settings.update']);
        $role->permissions()->attach([$viewPerm->id, $updatePerm->id]);
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::actingAs($user)->test(\App\Livewire\SettingsPage::class)
            ->call('save')
            ->assertSet('saved', true);
    }
}
