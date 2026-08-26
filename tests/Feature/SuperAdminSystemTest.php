<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        Role::firstOrCreate(['slug' => 'farmer'], ['name' => 'Farmer']);
    }

    public function test_only_super_admin_can_open_admin_pages(): void
    {
        $farmer = User::factory()->create();
        $farmer->assignRole('farmer');

        $this->actingAs($farmer)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_pages_render_one_shared_sidebar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);

        foreach (['admin.dashboard', 'admin.users', 'admin.activity-logs'] as $routeName) {
            $response = $this->get(route($routeName));

            $response->assertOk();
            $this->assertSame(1, substr_count($response->getContent(), 'Super Admin Console'), $routeName.' should render one shared sidebar.');
        }
    }

    public function test_greenhouse_management_routes_are_removed(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get('/admin/greenhouses')->assertNotFound();
        $this->actingAs($admin)->get('/greenhouses/1/dashboard')->assertNotFound();
    }

    public function test_logout_redirects_to_login_and_records_activity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

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
