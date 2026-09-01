<?php

namespace App\Livewire\Admin;

use App\Models\Device;
use App\Models\Telemetry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class ParameterLogs extends Component
{
    use WithPagination;

    public string $deviceId = '';

    public string $fromDate = '';

    public string $toDate = '';

    protected $queryString = [
        'deviceId' => ['except' => ''],
        'fromDate' => ['except' => ''],
        'toDate' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['deviceId', 'fromDate', 'toDate'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->deviceId = '';
        $this->fromDate = '';
        $this->toDate = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.parameter-logs', [
            'devices' => Device::query()
                ->select(['id', 'device_id', 'name'])
                ->orderBy('name')
                ->orderBy('device_id')
                ->get(),
            'telemetryLogs' => $this->telemetryQuery()->paginate(25),
        ]);
    }

    protected function telemetryQuery(): Builder
    {
        $deviceId = trim($this->deviceId);
        $from = $this->parseDateBoundary($this->fromDate);
        $to = $this->parseDateBoundary($this->toDate, true);

        return Telemetry::query()
            ->with('device:id,device_id,name')
            ->select([
                'id',
                'device_id',
                'air_temperature',
                'water_temperature',
                'ph',
                'ec',
                'water_flow',
                'water_level',
                'measured_at',
                'received_at',
                'created_at',
            ])
            ->when(ctype_digit($deviceId), fn (Builder $query) => $query->where('device_id', (int) $deviceId))
            ->when($from, fn (Builder $query) => $query->where('measured_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('measured_at', '<=', $to))
            ->latestReading();
    }

    protected function parseDateBoundary(string $value, bool $endOfDay = false): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }
}
