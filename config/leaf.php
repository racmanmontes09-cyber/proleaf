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
        'device_db_id' => (int) env('LEAF_DASHBOARD_DEVICE_DB_ID', 358),
        'fake_telemetry_firmware' => env('LEAF_DASHBOARD_FAKE_TELEMETRY_FIRMWARE', 'leaf-fake-dashboard-telemetry'),
        'live_chart' => [
            'max_points' => (int) env('LEAF_DASHBOARD_MAX_VISIBLE_POINTS', 120),
            'buffer_points' => (int) env('LEAF_DASHBOARD_INTERNAL_BUFFER_POINTS', 150),
            'poll_interval_ms' => 1000,
            'poll_batch_limit' => 120,
            'realtime_enabled' => env('LEAF_DASHBOARD_REALTIME_TELEMETRY', true),
            'polling_fallback_enabled' => env('LEAF_DASHBOARD_TELEMETRY_POLLING_FALLBACK', false),
            'debug' => env('LEAF_DASHBOARD_CHART_DEBUG', false),
        ],
    ],
    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'topic' => env('MQTT_TOPIC', 'devices/+/telemetry'),
        'client_id' => env('MQTT_CLIENT_ID', 'leaf-mqtt-subscriber'),
        'qos' => (int) env('MQTT_QOS', 0),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'use_tls' => env('MQTT_USE_TLS', false),
    ],
    'simulation' => [
        'device_token' => env('LEAF_SIMULATION_DEVICE_TOKEN'),
        'device_tokens' => env('LEAF_SIMULATION_DEVICE_TOKENS'),
    ],
];
