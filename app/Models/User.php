<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Role;
use App\Models\Permission;

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
        return $this->roles()->where(function ($q) use ($role) {
            $q->where('slug', $role)->orWhere('name', $role);
        })->exists();
    }

    public function assignRole($role): void
    {
        if ($role instanceof Role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
            return;
        }

        $roleModel = Role::where('slug', $role)->orWhere('name', $role)->first();

        if ($roleModel) {
            $this->roles()->syncWithoutDetaching([$roleModel->id]);
        }
    }

    public function removeRole($role): void
    {
        if ($role instanceof Role) {
            $this->roles()->detach($role->id);
            return;
        }

        $roleModel = Role::where('slug', $role)->orWhere('name', $role)->first();

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }

    public function syncRoles(array $roles): void
    {
        $ids = Role::whereIn('slug', $roles)->orWhereIn('name', $roles)->pluck('id')->all();
        $this->roles()->sync($ids);
    }

    public function permissions()
    {
        $roleIds = $this->roles()->pluck('roles.id')->all();
        return Permission::query()->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $roleIds))->get();
    }

    public function hasPermission(string $permission): bool
    {
        return Permission::query()->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $this->roles()->pluck('roles.id')->all()))
            ->where(function ($q) use ($permission) {
                $q->where('slug', $permission)->orWhere('name', $permission);
            })->exists();
    }

    public function canPerform(string $permission): bool
    {
        return $this->hasPermission($permission) || $this->hasRole(config('rbac.super_admin_role', 'super-admin'));
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }
}