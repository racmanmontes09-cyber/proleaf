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
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Administrator', 'slug' => 'administrator'],
            ['name' => 'Farm Manager', 'slug' => 'farm-manager'],
            ['name' => 'Technician', 'slug' => 'technician'],
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
            ['name' => 'Telemetry: Export', 'slug' => 'telemetry.export'],
            ['name' => 'Alerts: View', 'slug' => 'alerts.view'],
            ['name' => 'Alerts: Acknowledge', 'slug' => 'alerts.acknowledge'],
            ['name' => 'Alerts: Delete', 'slug' => 'alerts.delete'],
            ['name' => 'Commands: Issue', 'slug' => 'commands.issue'],
            ['name' => 'Commands: Cancel', 'slug' => 'commands.cancel'],
            ['name' => 'Settings: View', 'slug' => 'settings.view'],
            ['name' => 'Settings: Update', 'slug' => 'settings.update'],
            ['name' => 'Reports: View', 'slug' => 'reports.view'],
            ['name' => 'Reports: Export', 'slug' => 'reports.export'],
            ['name' => 'Users: View', 'slug' => 'users.view'],
            ['name' => 'Users: Create', 'slug' => 'users.create'],
            ['name' => 'Users: Update', 'slug' => 'users.update'],
            ['name' => 'Users: Delete', 'slug' => 'users.delete'],
            ['name' => 'OTA: Deploy', 'slug' => 'ota.deploy'],
            ['name' => 'System: Manage', 'slug' => 'system.manage'],
        ];

        foreach ($perms as $p) {
            Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // Assign broad permissions to roles
        $super = Role::where('slug', 'super-admin')->first();
        $admin = Role::where('slug', 'administrator')->first();
        $manager = Role::where('slug', 'farm-manager')->first();
        $tech = Role::where('slug', 'technician')->first();
        $viewer = Role::where('slug', 'viewer')->first();

        $allPermIds = Permission::pluck('id')->all();

        if ($super) {
            $super->permissions()->sync($allPermIds);
        }

        if ($admin) {
            $admin->permissions()->sync($allPermIds);
        }

        if ($manager) {
            $managerPerms = Permission::whereIn('slug', [
                'dashboard.view','devices.view','telemetry.view','alerts.view','alerts.acknowledge','commands.issue','reports.view','reports.export',
            ])->pluck('id')->all();
            $manager->permissions()->sync($managerPerms);
        }

        if ($tech) {
            $techPerms = Permission::whereIn('slug', ['devices.view','commands.issue','telemetry.view'])->pluck('id')->all();
            $tech->permissions()->sync($techPerms);
        }

        if ($viewer) {
            $viewerPerms = Permission::whereIn('slug', ['dashboard.view','devices.view','telemetry.view','reports.view'])->pluck('id')->all();
            $viewer->permissions()->sync($viewerPerms);
        }
    }
}
