<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class NavigationSimplificationTest extends TestCase
{
    public function test_only_dashboard_routes_remain_available(): void
    {
        $this->assertTrue(Route::has('dashboard'));
        $this->assertStringEndsWith('/dashboard', route('dashboard'));

        $this->assertFalse(Route::has('monitoring'));
        $this->assertFalse(Route::has('device-management'));
        $this->assertFalse(Route::has('alerts-logs'));
        $this->assertFalse(Route::has('settings'));
        // The `profile` route is provided by auth routes and is expected to exist.
        $this->assertTrue(Route::has('profile'));
        $this->assertFalse(Route::has('analytics'));
        $this->assertFalse(Route::has('reports'));
    }
}
