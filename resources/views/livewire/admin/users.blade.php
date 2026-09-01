    <div class="space-y-8">
        <x-leaf.page-header title="Users" subtitle="Manage campus accounts, roles, and status." badge="Admin" />
        @if (session('status')) <div class="rounded-xl bg-emerald-50 dark:bg-emerald-500/10 px-4 py-3 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-emerald-700 dark:text-emerald-300">{{ session('status') }}</div> @endif
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="overflow-x-auto rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white shadow-sm dark:bg-[#1E293B]">
                <table class="min-w-full divide-y divide-[#2D6A4F]/10 dark:divide-white/10 text-left text-sm lg:text-[15px] lg:leading-6 max-sm:text-[13px] max-sm:leading-5">
                    <thead class="bg-[#F8FAF8] dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 uppercase tracking-normal opacity-70"><tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Role</th><th class="px-5 py-3">Status</th><th></th></tr></thead>
                    <tbody class="divide-y divide-[#2D6A4F]/10 dark:divide-white/10 dark:text-slate-300">
                        @forelse ($users as $user)
                            <tr><td class="px-5 py-4 font-semibold">{{ $user->name }}</td><td class="px-5 py-4">{{ $user->email }}</td><td class="px-5 py-4">{{ $user->primaryRoleName() }}</td><td class="px-5 py-4"><span class="font-semibold {{ $user->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td><td class="space-x-2 whitespace-nowrap px-5 py-4 text-right"><button wire:click="edit({{ $user->id }})" class="font-semibold text-[#2D6A4F] dark:text-leaf-300">Edit</button>@if (! $user->is(auth()->user()))<button wire:click="toggleActive({{ $user->id }})" class="font-semibold text-[#2D6A4F] dark:text-leaf-300">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>@endif</td></tr>
                        @empty <tr><td colspan="5" class="px-5 py-12 text-center opacity-70">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <form wire:submit="save" class="space-y-4 rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white p-5 shadow-sm dark:bg-[#1E293B]">
                <h2 class="text-lg lg:text-xl lg:leading-7 font-bold text-[#1B4332] dark:text-slate-200">{{ $editingId ? 'Edit user' : 'Add user' }}</h2>
                <label class="block text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold">Full name<input wire:model="name" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 dark:bg-[#0F172A] dark:text-slate-200 px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5" /></label>
                <label class="block text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold">Email<input type="email" wire:model="email" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 dark:bg-[#0F172A] dark:text-slate-200 px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5" /></label>
                <label class="block text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold">Password<input type="password" wire:model="password" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 dark:bg-[#0F172A] dark:text-slate-200 px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5" placeholder="{{ $editingId ? 'Leave blank to keep current' : '' }}" /></label>
                <label class="block text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold">Role<select wire:model="roleId" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 dark:bg-[#0F172A] dark:text-slate-200 px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5"><option value="">Select role</option>@foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></label>
                <label class="flex items-center gap-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold"><input type="checkbox" wire:model="isActive" /> Active account</label>
                <div class="flex gap-2"><button class="flex-1 rounded-xl bg-[#2D6A4F] px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-white">{{ $editingId ? 'Update' : 'Create' }}</button>@if ($editingId)<button type="button" wire:click="resetForm" class="rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 dark:bg-[#1E293B] dark:text-slate-200 px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold">Cancel</button>@endif</div>
            </form>
        </div>
    </div>
