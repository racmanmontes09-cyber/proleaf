<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;

class DeviceStatus extends Component
{
   public function render()
{
    return view('livewire.dashboard.device-status', [
        'device' => Device::latest()->first(),
    ]);
}
}
