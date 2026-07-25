<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'permission:foo.bar'])->get('/test-perm', function () {
            return response()->json(['ok' => true]);
        });
    }

    public function test_forbidden_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/test-perm')->assertStatus(403);
    }

    public function test_allowed_with_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Role', 'slug' => 'role']);
        $perm = Permission::create(['name' => 'Foo Bar', 'slug' => 'foo.bar']);
        $role->permissions()->attach($perm->id);
        $user->assignRole($role);

        $this->actingAs($user)->get('/test-perm')->assertStatus(200);
    }
}
