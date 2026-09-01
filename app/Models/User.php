<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Check whether the user has a role by slug or name.
     */
    public function hasRole(string $role): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                fn (Role $roleModel): bool => $roleModel->slug === $role || $roleModel->name === $role
            );
        }

        return $this->roles()->where(function ($q) use ($role): void {
            $q->where('slug', $role)->orWhere('name', $role);
        })->exists();
    }

    public function assignRole($role): void
    {
        if ($role instanceof Role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
            $this->forgetRoleRelations();
            return;
        }

        $roleModel = Role::where('slug', $role)->orWhere('name', $role)->first();

        if ($roleModel) {
            $this->roles()->syncWithoutDetaching([$roleModel->id]);
            $this->forgetRoleRelations();
        }
    }

    public function removeRole($role): void
    {
        if ($role instanceof Role) {
            $this->roles()->detach($role->id);
            $this->forgetRoleRelations();
            return;
        }

        $roleModel = Role::where('slug', $role)->orWhere('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->forgetRoleRelations();
        }
    }

    public function syncRoles(array $roles): void
    {
        $ids = Role::whereIn('slug', $roles)->orWhereIn('name', $roles)->pluck('id')->all();
        $this->roles()->sync($ids);
        $this->forgetRoleRelations();
    }

    public function permissions()
    {
        $roleIds = $this->roleIdsForPermissionLookup();

        if ($roleIds === []) {
            return collect();
        }

        return Permission::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))
            ->get();
    }

    public function greenhouses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Greenhouse::class, 'user_id');
    }

    public function greenhouse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Greenhouse::class, 'user_id')->latestOfMany();
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function preferences(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->relationLoaded('roles') && $this->roles->every->relationLoaded('permissions')) {
            return $this->roles->contains(function (Role $role) use ($permission): bool {
                return $role->permissions->contains(
                    fn (Permission $permissionModel): bool => $permissionModel->slug === $permission || $permissionModel->name === $permission
                );
            });
        }

        return $this->roles()
            ->whereHas('permissions', function ($q) use ($permission): void {
                $q->where('slug', $permission)->orWhere('name', $permission);
            })
            ->exists();
    }

    public function canPerform(string $permission): bool
    {
        return $this->isAdmin() || $this->hasPermission($permission);
    }

    /**
     * Determine whether the user has the admin role (full access).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(config('rbac.admin_role', 'admin'));
    }

    /**
     * Determine whether the user has the viewer role (read-only access).
     */
    public function isViewer(): bool
    {
        return $this->hasRole(config('rbac.viewer_role', 'viewer'));
    }

    public function primaryRoleName(): string
    {
        if ($this->isAdmin()) {
            return 'Admin';
        }

        $roleName = $this->roles->first()?->name;

        return match ($roleName) {
            'Telemetry Viewer', 'telemetry-viewer', 'Viewer', 'viewer' => 'Viewer',
            null, '' => 'Viewer',
            default => $roleName,
        };
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hasGreenhouseAssignment(): bool
    {
        if ($this->relationLoaded('greenhouse')) {
            return $this->greenhouse !== null;
        }

        if ($this->relationLoaded('greenhouses')) {
            return $this->greenhouses->isNotEmpty();
        }

        return $this->greenhouse()->exists();
    }

    private function roleIdsForPermissionLookup(): array
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->pluck('id')->all();
        }

        return $this->roles()->pluck('roles.id')->all();
    }

    private function forgetRoleRelations(): void
    {
        $this->unsetRelation('roles');
    }
}
