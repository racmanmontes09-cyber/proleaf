<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Device;

class DeviceStatus extends Component
{
    public function render()
    {
        return view('livewire.dashboard.device-status', [
            'device' => Device::first(),
        ]);
    }
}