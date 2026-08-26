<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog as ActivityLogModel;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogs extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin() || auth()->user()?->hasRole('administrator'), 403);
    }

    public function render()
    {
        return view('livewire.admin.activity-logs', [
            'activityLogs' => ActivityLogModel::query()
                ->with(['user', 'greenhouse', 'device'])
                ->latest()
                ->paginate(25),
        ]);
    }
}
