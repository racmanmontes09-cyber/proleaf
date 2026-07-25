<?php

namespace App\Livewire\Audit;

use App\Repositories\AuditRepository;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogList extends Component
{
    use WithPagination;

    public $user_id;
    public $device_id;
    public $action;
    public $from;
    public $to;

    protected $queryString = ['user_id', 'device_id', 'action', 'from', 'to'];

    public function render(AuditRepository $repo)
    {
        if (! auth()->user()?->canPerform('audit.view')) {
            abort(403);
        }

        $filters = [
            'user_id' => $this->user_id,
            'device_id' => $this->device_id,
            'action' => $this->action,
            'from' => $this->from,
            'to' => $this->to,
        ];

        $logs = $repo->search($filters, 25);

        return view('livewire.audit.logs', ['logs' => $logs]);
    }
}
