<?php

namespace App\Services;

use App\Repositories\AuditRepository;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(protected AuditRepository $repo)
    {
    }

    public function log(string $action, array $context = []): void
    {
        $old = $context['old'] ?? null;
        $new = $context['new'] ?? null;

        $data = [
            'user_id' => $context['user_id'] ?? auth()->id() ?? null,
            'device_id' => $context['device_id'] ?? null,
            'action' => $action,
            'model_type' => $context['model_type'] ?? null,
            'model_id' => $context['model_id'] ?? null,
            'old_values' => $old ? $this->sanitizeValues($old) : null,
            'new_values' => $new ? $this->sanitizeValues($new) : null,
            'ip_address' => $context['ip'] ?? request()->ip() ?? null,
            'user_agent' => $context['user_agent'] ?? request()->userAgent() ?? null,
            'route' => $context['route'] ?? request()->path() ?? null,
        ];

        $this->repo->create($data);
    }

    /**
     * Remove sensitive keys from arrays recursively.
     */
    private function sanitizeValues(array $values): array
    {
        $sensitiveKeys = ['password', 'device_token', 'remember_token', 'api_token', 'personal_access_token'];

        $clean = [];

        foreach ($values as $key => $value) {
            // Skip obviously sensitive keys
            if (in_array($key, $sensitiveKeys, true) || preg_match('/(token|secret|key|credential)/i', (string) $key)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeValues($value);
            } else {
                // Cast objects to string if possible
                if (is_object($value)) {
                    if (method_exists($value, 'toArray')) {
                        $clean[$key] = $this->sanitizeValues($value->toArray());
                    } else {
                        $clean[$key] = (string) $value;
                    }
                } else {
                    $clean[$key] = $value;
                }
            }
        }

        return $clean;
    }
}
