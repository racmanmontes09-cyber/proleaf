<?php

namespace Tests\Feature;

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
}
