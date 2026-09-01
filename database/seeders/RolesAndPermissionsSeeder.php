<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Viewer', 'slug' => 'viewer'],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        $perms = [
            ['name' => 'Dashboard: View', 'slug' => 'dashboard.view'],
            ['name' => 'Devices: View', 'slug' => 'devices.view'],
            ['name' => 'Devices: Create', 'slug' => 'devices.create'],
            ['name' => 'Devices: Update', 'slug' => 'devices.update'],
            ['name' => 'Devices: Delete', 'slug' => 'devices.delete'],
            ['name' => 'Telemetry: View', 'slug' => 'telemetry.view'],
            ['name' => 'Alerts: View', 'slug' => 'alerts.view'],
            ['name' => 'Alerts: Acknowledge', 'slug' => 'alerts.acknowledge'],
            ['name' => 'Alerts: Delete', 'slug' => 'alerts.delete'],
            ['name' => 'Commands: Issue', 'slug' => 'commands.issue'],
            ['name' => 'Commands: Cancel', 'slug' => 'commands.cancel'],
            ['name' => 'Settings: View', 'slug' => 'settings.view'],
            ['name' => 'Settings: Update', 'slug' => 'settings.update'],
            ['name' => 'Users: View', 'slug' => 'users.view'],
            ['name' => 'Users: Create', 'slug' => 'users.create'],
            ['name' => 'Users: Update', 'slug' => 'users.update'],
            ['name' => 'Users: Delete', 'slug' => 'users.delete'],
            ['name' => 'Activity: View', 'slug' => 'activity.view'],
            ['name' => 'System: Manage', 'slug' => 'system.manage'],
        ];

        foreach ($perms as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // Assign permissions to the two canonical roles.
        $admin = Role::where('slug', 'admin')->first();
        $viewer = Role::where('slug', 'viewer')->first();

        $allPermIds = Permission::pluck('id')->all();

        if ($admin) {
            $admin->permissions()->sync($allPermIds);
        }

        if ($viewer) {
            $viewerPerms = Permission::whereIn('slug', [
                'dashboard.view', 'devices.view', 'telemetry.view',
            ])->pluck('id')->all();

            $viewer->permissions()->sync($viewerPerms);
        }
    }
}
