<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class AdminDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
