<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_and_remove_role(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Test Role', 'slug' => 'test-role']);

        $this->assertFalse($user->hasRole('test-role'));

        $user->assignRole('test-role');
        $this->assertTrue($user->hasRole('test-role'));

        $user->removeRole('test-role');
        $this->assertFalse($user->hasRole('test-role'));
    }
}
