<?php

namespace Tests\Feature;

use App\Services\AuditService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $audit = app(AuditService::class);
        $audit->log('test.action', ['new' => ['foo' => 'bar']]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'test.action']);
    }
}
