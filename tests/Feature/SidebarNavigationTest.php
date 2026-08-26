<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_remains_the_primary_authenticated_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertSee('Project L.E.A.F.');
    }

    public function test_dashboard_hides_specific_role_and_branding_labels(): void
    {
        $permission = Permission::create(['name' => 'Telemetry: View', 'slug' => 'telemetry.view']);
        $role = Role::create(['name' => 'Telemetry Viewer', 'slug' => 'telemetry-viewer']);
        $role->permissions()->attach($permission->id);

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Telemetry Viewer')
            ->assertDontSee('DA / Project L.E.A.F.');
    }
}
