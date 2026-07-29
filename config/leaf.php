<?php

return [
    'device_api' => [
        'rate_limits' => [
            'heartbeat_per_minute' => 60,
            'telemetry_per_minute' => 120,
            'commands_per_minute' => 60,
        ],
    ],
    'simulation' => [
        'device_token' => env('LEAF_SIMULATION_DEVICE_TOKEN'),
        'device_tokens' => env('LEAF_SIMULATION_DEVICE_TOKENS'),
    ],
];
