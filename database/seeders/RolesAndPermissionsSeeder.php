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
            ['name' => 'Farmer', 'slug' => 'farmer'],
            ['name' => 'Administrator', 'slug' => 'administrator'],
            ['name' => 'Farm Manager', 'slug' => 'farm-manager'],
            ['name' => 'Technician', 'slug' => 'technician'],
            ['name' => 'Viewer', 'slug' => 'viewer'],
            ['name' => 'Greenhouse Staff', 'slug' => 'greenhouse-staff'],
            ['name' => 'Researcher', 'slug' => 'researcher'],
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

        // Assign broad permissions to roles
        $super = Role::where('slug', 'super-admin')->first();
        $farmer = Role::where('slug', 'farmer')->first();
        $admin = Role::where('slug', 'administrator')->first();
        $manager = Role::where('slug', 'farm-manager')->first();
        $tech = Role::where('slug', 'technician')->first();
        $viewer = Role::where('slug', 'viewer')->first();
        $staff = Role::where('slug', 'greenhouse-staff')->first();
        $researcher = Role::where('slug', 'researcher')->first();

        $allPermIds = Permission::pluck('id')->all();

        if ($super) {
            $super->permissions()->sync($allPermIds);
        }

        if ($farmer) {
            $farmerPerms = Permission::whereIn('slug', [
                'dashboard.view', 'devices.view', 'telemetry.view',
                'alerts.view', 'alerts.acknowledge', 'commands.issue',
            ])->pluck('id')->all();
            $farmer->permissions()->sync($farmerPerms);
        }

        if ($admin) {
            $admin->permissions()->sync($allPermIds);
        }

        if ($manager) {
            $managerPerms = Permission::whereIn('slug', [
                'dashboard.view', 'devices.view', 'telemetry.view',
                'alerts.view', 'alerts.acknowledge', 'commands.issue',
            ])->pluck('id')->all();
            $manager->permissions()->sync($managerPerms);
        }

        if ($tech) {
            $techPerms = Permission::whereIn('slug', ['devices.view', 'commands.issue', 'telemetry.view'])->pluck('id')->all();
            $tech->permissions()->sync($techPerms);
        }

        if ($viewer) {
            $viewerPerms = Permission::whereIn('slug', ['dashboard.view', 'devices.view', 'telemetry.view'])->pluck('id')->all();
            $viewer->permissions()->sync($viewerPerms);
        }

        if ($staff) {
            $staffPerms = Permission::whereIn('slug', [
                'dashboard.view', 'devices.view', 'telemetry.view',
                'alerts.view', 'alerts.acknowledge', 'commands.issue',
            ])->pluck('id')->all();
            $staff->permissions()->sync($staffPerms);
        }

        if ($researcher) {
            $researcherPerms = Permission::whereIn('slug', [
                'dashboard.view', 'devices.view', 'telemetry.view', 'alerts.view',
            ])->pluck('id')->all();
            $researcher->permissions()->sync($researcherPerms);
        }
    }
}
