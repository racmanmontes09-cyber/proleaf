<?php

return [
    'device_api' => [
        'rate_limits' => [
            'heartbeat_per_minute' => 60,
            'telemetry_per_minute' => 120,
            'commands_per_minute' => 60,
        ],
    ],
];
