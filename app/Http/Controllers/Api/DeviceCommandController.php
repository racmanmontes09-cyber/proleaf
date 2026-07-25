<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceCommandUpdateRequest;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Repositories\DeviceCommandRepository;
use App\Services\DeviceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceCommandController extends Controller
{
    public function __construct(
        protected DeviceCommandService $commandService,
        protected DeviceCommandRepository $commandRepository
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $commands = $this->commandService->claimPendingCommands($device);

        return response()->json([
            'success' => true,
            'message' => 'Pending commands retrieved.',
            'data' => [
                'commands' => $commands->map(fn (DeviceCommand $command) => $this->serializeCommand($command))->values(),
            ],
        ]);
    }

    public function update(DeviceCommandUpdateRequest $request, int $commandId): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');
        $command = $this->commandRepository->forDeviceById($device, $commandId);

        if ($command === null) {
            return response()->json([
                'success' => false,
                'message' => 'Command not found for this device.',
            ], 404);
        }

        $input = $request->validated();

        if ($input['status'] === DeviceCommand::STATUS_COMPLETED) {
            $command = $this->commandService->markCommandCompleted($command, $input['result'] ?? []);
        } else {
            $command = $this->commandService->markCommandFailed($command, $input['failure_reason'] ?? 'Command execution failed.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Device command updated successfully.',
            'data' => $this->serializeCommand($command),
        ]);
    }

    protected function serializeCommand(DeviceCommand $command): array
    {
        return [
            'id' => $command->id,
            'command' => $command->command,
            'title' => $command->title,
            'payload' => is_array($command->payload) ? $command->payload : (array) $command->payload,
            'status' => $command->status,
            'attempt_count' => $command->attempt_count,
            'max_attempts' => $command->max_attempts,
            'delivered_at' => $command->delivered_at?->toIso8601String(),
            'executed_at' => $command->executed_at?->toIso8601String(),
            'expires_at' => $command->expires_at?->toIso8601String(),
            'result' => is_array($command->result) ? $command->result : (array) $command->result,
            'failure_reason' => $command->failure_reason,
            'metadata' => is_array($command->metadata) ? $command->metadata : (array) $command->metadata,
            'created_at' => $command->created_at?->toIso8601String(),
            'updated_at' => $command->updated_at?->toIso8601String(),
        ];
    }
}
