<?php

namespace Tests\Feature\Auth;

use App\Livewire\Actions\Logout;
use App\Models\ActivityLog;
use App\Livewire\Admin\Users as UsersComponent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_selected_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $role = Role::where('slug', 'viewer')->firstOrFail();

        Volt::actingAs($admin)->test('admin.users')
            ->set('name', 'Campus Viewer')
            ->set('email', 'viewer@ispsc.edu.ph')
            ->set('password', 'temporary-password')
            ->set('roleId', $role->id)
            ->call('save');

        $user = User::where('email', 'viewer@ispsc.edu.ph')->firstOrFail();
        $this->assertTrue($user->hasRole('viewer'));
        $this->assertTrue($user->is_active);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('temporary-password', $user->password));
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors();

        $this->assertGuest();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_viewer_is_redirected_to_regular_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_dashboard_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Project L.E.A.F.')
            ->assertSee('ESP32 Controller Node');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $logoutAction = new Logout();
        $logoutAction();

        $this->assertGuest();
    }

    public function test_admin_and_viewer_logout_through_the_same_flow(): void
    {
        foreach (['admin', 'viewer'] as $roleSlug) {
            $user = User::factory()->create();
            $user->assignRole($roleSlug);

            $this->actingAs($user)
                ->post(route('logout'))
                ->assertRedirect(route('login'));

            $this->assertGuest();
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $user->id,
                'action' => 'Logout',
                'target' => 'Web Application',
            ]);
        }
    }
}
