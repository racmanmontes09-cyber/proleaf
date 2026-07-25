<div>
    <div class="space-y-2">
        <form wire:submit.prevent>
            <div class="flex gap-2">
                <input wire:model.defer="user_id" placeholder="User ID" class="input" />
                <input wire:model.defer="device_id" placeholder="Device ID" class="input" />
                <input wire:model.defer="action" placeholder="Action" class="input" />
                <input wire:model.defer="from" type="date" class="input" />
                <input wire:model.defer="to" type="date" class="input" />
                <button wire:click="$refresh" type="button" class="btn">Search</button>
            </div>
        </form>

        <div class="overflow-auto mt-4">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Device</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->user?->name ?? $log->user_id }}</td>
                            <td>{{ $log->device_id }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ class_basename($log->model_type) }}: {{ $log->model_id }}</td>
                            <td>
                                <div class="text-xs">
                                    <strong>Old:</strong> {{ json_encode($log->old_values) }}
                                    <br />
                                    <strong>New:</strong> {{ json_encode($log->new_values) }}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
