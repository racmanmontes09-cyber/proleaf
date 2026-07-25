<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_pages_render_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::create(['name' => 'Viewer', 'slug' => 'viewer']);
        $viewPerm = \App\Models\Permission::create(['name' => 'Settings View', 'slug' => 'settings.view']);
        $role->permissions()->attach($viewPerm->id);
        $user->assignRole($role);

        $this->actingAs($user);

        $this->get(route('monitoring'))->assertOk()->assertSee('Monitoring');
        $this->get(route('analytics'))->assertOk()->assertSee('Analytics');
        $this->get(route('device-management'))->assertOk()->assertSee('Device Management');
        $this->get(route('alerts-logs'))->assertOk()->assertSee('Alerts & Logs');
        $this->get(route('reports'))->assertOk()->assertSee('Reports & Export');
        $this->get(route('settings'))->assertOk()->assertSee('System Settings');
    }
}
