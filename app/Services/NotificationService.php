<?php

namespace App\Services;

class NotificationService
{
    public function queue(string $channel, array $payload): void
    {
        // Queue notification payload for later delivery.
        // Future integrations should push this payload into a message queue or notification bus.
    }

    public function dispatch(string $channel, array $payload): void
    {
        // Dispatch a notification payload to the selected channel.
        // Actual transport implementations for Email, SMS, Telegram, Firebase, WebSocket will be added later.
    }

    public function buildPayload(array $data): array
    {
        return [
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'severity' => $data['severity'] ?? 'info',
            'metadata' => $data['metadata'] ?? [],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public function markDelivered(int $alertId): void
    {
        // Mark the notification delivery state for the alert.
        // This can be extended to update a notification delivery table in future.
    }
}
