    <div class="space-y-8">
        <x-leaf.page-header title="Users" subtitle="Manage campus accounts, roles, and status." badge="Super Admin" />
        @if (session('status')) <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div> @endif
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="overflow-x-auto rounded-2xl border border-[#2D6A4F]/10 bg-white shadow-sm dark:bg-slate-900">
                <table class="min-w-full divide-y divide-[#2D6A4F]/10 text-left text-sm">
                    <thead class="bg-[#F8FAF8] text-xs uppercase tracking-wider opacity-70 dark:bg-slate-800"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-[#2D6A4F]/10">
                        @forelse ($users as $user)
                            <tr><td class="px-5 py-4 font-semibold">{{ $user->name }}</td><td class="px-5 py-4">{{ $user->email }}</td><td class="px-5 py-4">{{ $user->primaryRoleName() }}</td><td class="px-5 py-4"><span class="font-semibold {{ $user->is_active ? 'text-emerald-600' : 'text-rose-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td><td class="space-x-2 whitespace-nowrap px-5 py-4 text-right"><button wire:click="edit({{ $user->id }})" class="font-semibold text-[#2D6A4F]">Edit</button>@if (! $user->is(auth()->user()))<button wire:click="toggleActive({{ $user->id }})" class="font-semibold text-[#2D6A4F]">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>@endif</td></tr>
                        @empty <tr><td colspan="5" class="px-5 py-12 text-center opacity-70">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form wire:submit="save" class="space-y-4 rounded-2xl border border-[#2D6A4F]/10 bg-white p-5 shadow-sm dark:bg-slate-900">
                <h2 class="font-bold">{{ $editingId ? 'Edit user' : 'Add user' }}</h2>
                <label class="block text-sm font-semibold">Full name<input wire:model="name" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 px-3 py-2" /></label>
                <label class="block text-sm font-semibold">Email<input type="email" wire:model="email" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 px-3 py-2" /></label>
                <label class="block text-sm font-semibold">Password<input type="password" wire:model="password" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 px-3 py-2" placeholder="{{ $editingId ? 'Leave blank to keep current' : '' }}" /></label>
                <label class="block text-sm font-semibold">Role<select wire:model="roleId" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 px-3 py-2"><option value="">Select role</option>@foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></label>
                <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" wire:model="isActive" /> Active account</label>
                <div class="flex gap-2"><button class="flex-1 rounded-xl bg-[#2D6A4F] px-4 py-2 font-semibold text-white">{{ $editingId ? 'Update' : 'Create' }}</button>@if ($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border border-[#2D6A4F]/20 px-4 py-2 font-semibold">Cancel</button>@endif</div>
            </form>
        </div>
    </div>
