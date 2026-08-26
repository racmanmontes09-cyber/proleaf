<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Users extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?int $roleId = null;
    public bool $isActive = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin() || auth()->user()?->hasRole('administrator'), 403);
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($this->editingId ?? 'NULL').',id'],
            'roleId' => ['required', 'integer', 'exists:roles,id'],
            'isActive' => ['boolean'],
        ];
        if ($this->editingId === null) {
            $rules['password'] = ['required', 'string', 'min:8'];
        } elseif ($this->password !== '') {
            $rules['password'] = ['string', 'min:8'];
        }
        $this->validate($rules);

        $user = $this->editingId ? User::findOrFail($this->editingId) : new User;
        $role = Role::findOrFail($this->roleId);
        $currentUser = auth()->user();
        if ($user->exists && $user->is($currentUser)) {
            abort_unless($this->isActive && $role->slug === config('rbac.super_admin_role', 'super-admin'), 403);
        }
        $wasNew = ! $user->exists;
        $user->name = $this->name;
        $user->email = $this->email;
        $user->is_active = $this->isActive;
        if ($this->password !== '') {
            $user->password = Hash::make($this->password);
        }
        $user->save();
        $user->syncRoles([$role->slug]);

        ActivityLogger::log(
            action: $wasNew ? 'User creation' : 'User update',
            target: $user->email,
            details: ['user_id' => $user->id, 'role' => $role->name],
            user: $user,
        );

        $this->resetForm();
        session()->flash('status', $wasNew ? 'Farmer created.' : 'Farmer updated.');
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roleId = $user->roles->first()?->id;
        $this->isActive = (bool) $user->is_active;
        $this->password = '';
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        abort_unless(! $user->is(auth()->user()) && ! $user->isSuperAdmin(), 403);
        $user->update(['is_active' => ! $user->is_active]);
        ActivityLogger::log('User update', $user->email, ['user_id' => $user->id, 'is_active' => $user->is_active], user: $user);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'roleId']);
        $this->isActive = true;
    }

    public function render()
    {
        return view('livewire.admin.users', [
            'users' => User::query()->with('roles')->latest()->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }
}
