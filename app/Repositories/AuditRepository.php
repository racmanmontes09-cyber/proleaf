<?php

namespace App\Repositories;

use App\Models\AuditLog;

class AuditRepository
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function search(array $filters, int $perPage = 50)
    {
        $query = AuditLog::query()->with('user', 'device');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['device_id'])) {
            $query->where('device_id', $filters['device_id']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
