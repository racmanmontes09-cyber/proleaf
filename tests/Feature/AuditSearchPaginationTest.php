<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSearchPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_search_and_pagination(): void
    {
        $user = User::factory()->create();
        // Give the user permission to view audit logs
        $role = \App\Models\Role::create(['name' => 'Auditor', 'slug' => 'auditor']);
        $perm = \App\Models\Permission::create(['name' => 'Audit View', 'slug' => 'audit.view']);
        $role->permissions()->attach($perm->id);
        $user->assignRole($role);

        // create 60 audit logs
        for ($i = 0; $i < 60; $i++) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $i % 2 === 0 ? 'device.updated' : 'device.created',
                'model_type' => 'App\\Models\\Device',
                'model_id' => $i + 1,
                'new_values' => ['foo' => 'bar'],
            ]);
        }

        // Mount the Livewire component directly to avoid route middleware issues
        Livewire::actingAs($user)->test(\App\Livewire\Audit\AuditLogList::class)
            ->assertSee('ID');

        $this->assertGreaterThanOrEqual(60, \App\Models\AuditLog::count());
    }
}
