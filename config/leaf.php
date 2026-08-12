<?php

return [
    'device_api' => [
        'rate_limits' => [
            'heartbeat_per_minute' => 60,
            'telemetry_per_minute' => 120,
            'commands_per_minute' => 60,
        ],
    ],
    'dashboard' => [
        'live_chart' => [
            'max_points' => 60,
            'poll_interval_ms' => 1000,
            'poll_batch_limit' => 120,
        ],
    ],
    'simulation' => [
        'device_token' => env('LEAF_SIMULATION_DEVICE_TOKEN'),
        'device_tokens' => env('LEAF_SIMULATION_DEVICE_TOKENS'),
    ],
];
