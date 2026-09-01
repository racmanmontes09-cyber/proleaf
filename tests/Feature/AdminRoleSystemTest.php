<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
    }

    public function test_only_admin_can_open_admin_pages(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->actingAs($viewer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.parameter-logs'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.system-parameters'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.parameter-logs'))->assertOk();
        $this->actingAs($admin)->get(route('admin.system-parameters'))->assertOk();
    }

    public function test_admin_pages_render_one_shared_sidebar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        foreach (['admin.dashboard', 'admin.parameter-logs', 'admin.system-parameters', 'admin.users', 'admin.activity-logs'] as $routeName) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $this->assertStringContainsString('adminSidebar()', $response->getContent(), $routeName.' should render one shared sidebar.');
        }
    }

    public function test_admin_dashboard_wraps_polling_device_status_child(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $html = $response->getContent();

        $wrapperPosition = strpos($html, 'data-admin-dashboard-root');
        $pollPosition = strpos($html, 'wire:poll.visible.500ms="refreshDashboardLight"');

        $this->assertNotFalse($wrapperPosition);
        $this->assertNotFalse($pollPosition);
        $this->assertLessThan(
            $pollPosition,
            $wrapperPosition,
            'The admin wrapper must render before the polling device-status root so Livewire sends poll calls to the child component.'
        );
        $this->assertSame(1, substr_count($html, 'data-admin-dashboard-root'));
    }

    public function test_greenhouse_management_routes_are_removed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/greenhouses')->assertNotFound();
        $this->actingAs($admin)->get('/greenhouses/1/dashboard')->assertNotFound();
    }

    public function test_logout_redirects_to_login_and_records_activity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'Logout',
            'target' => 'Web Application',
        ]);
    }
}