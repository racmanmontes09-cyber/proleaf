<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidate the legacy role set (super-admin, farmer, administrator,
 * farm-manager, technician, telemetry-viewer, greenhouse-staff, researcher)
 * into exactly two roles: admin (full access) and viewer (read-only).
 */
return new class extends Migration
{
    private const ADMIN_SLUG = 'admin';

    private const VIEWER_SLUG = 'viewer';

    /**
     * Legacy roles that map onto full admin access.
     */
    private const ADMIN_LEGACY_SLUGS = [
        'super-admin',
        'administrator',
        'farm-manager',
        'technician',
    ];

    /**
     * Legacy roles that map onto read-only viewer access.
     */
    private const VIEWER_LEGACY_SLUGS = [
        'farmer',
        'telemetry-viewer',
        'greenhouse-staff',
        'researcher',
    ];

    public function up(): void
    {
        // Ensure the two canonical roles exist even on fresh installs.
        $admin = Role::firstOrCreate(['slug' => self::ADMIN_SLUG], ['name' => 'Admin']);
        $viewer = Role::firstOrCreate(['slug' => self::VIEWER_SLUG], ['name' => 'Viewer']);

        // Admin: every permission. Viewer: read-only dashboard/devices/telemetry.
        $admin->permissions()->sync(Permission::pluck('id')->all());

        $viewer->permissions()->sync(
            Permission::whereIn('slug', [
                'dashboard.view',
                'devices.view',
                'telemetry.view',
            ])->pluck('id')->all()
        );

        // Reassign users from legacy roles before deleting those roles.
        $this->moveUsersToRole(self::ADMIN_LEGACY_SLUGS, $admin->id);
        $this->moveUsersToRole(self::VIEWER_LEGACY_SLUGS, $viewer->id);

        // Remove the legacy roles (cascade deletes their pivot rows).
        Role::whereIn('slug', array_merge(self::ADMIN_LEGACY_SLUGS, self::VIEWER_LEGACY_SLUGS))->delete();
    }

    public function down(): void
    {
        // Best-effort restoration of the legacy role shells. User/permission
        // mapping for the removed roles cannot be faithfully reconstructed.
        $legacy = [
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Farmer', 'slug' => 'farmer'],
            ['name' => 'Administrator', 'slug' => 'administrator'],
            ['name' => 'Farm Manager', 'slug' => 'farm-manager'],
            ['name' => 'Technician', 'slug' => 'technician'],
            ['name' => 'Telemetry Viewer', 'slug' => 'telemetry-viewer'],
            ['name' => 'Greenhouse Staff', 'slug' => 'greenhouse-staff'],
            ['name' => 'Researcher', 'slug' => 'researcher'],
        ];

        foreach ($legacy as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }

    /**
     * Attach every user assigned to one of the legacy roles to the target role.
     */
    private function moveUsersToRole(array $legacySlugs, int $targetRoleId): void
    {
        $legacyRoleIds = Role::whereIn('slug', $legacySlugs)->pluck('id')->all();

        if ($legacyRoleIds === []) {
            return;
        }

        $userIds = DB::table('role_user')
            ->whereIn('role_id', $legacyRoleIds)
            ->distinct()
            ->pluck('user_id')
            ->all();

        if ($userIds === []) {
            return;
        }

        $rows = array_map(
            fn ($userId): array => ['user_id' => (int) $userId, 'role_id' => $targetRoleId],
            $userIds
        );

        DB::table('role_user')->insertOrIgnore($rows);
    }
};