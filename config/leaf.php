<?php

return [
    'device_api' => [
        'rate_limits' => [
            'heartbeat_per_minute' => 60,
            'telemetry_per_minute' => 120,
            'commands_per_minute' => 60,
        ],
    ],
    'device_status' => [
        // Grace period must exceed the maximum expected heartbeat interval
        // plus network/MQTT jitter.  The backend also applies adaptive
        // scaling via Device::effectiveOnlineGraceSeconds().
        'online_grace_seconds' => (int) env('LEAF_DEVICE_ONLINE_GRACE_SECONDS', 60),
    ],
    'dashboard' => [
        'device_db_id' => (int) env('LEAF_DASHBOARD_DEVICE_DB_ID', 358),
        'fake_telemetry_firmware' => env('LEAF_DASHBOARD_FAKE_TELEMETRY_FIRMWARE', 'leaf-fake-dashboard-telemetry'),
        'live_chart' => [
            'max_points' => (int) env('LEAF_DASHBOARD_MAX_VISIBLE_POINTS', 720),
            'buffer_points' => (int) env('LEAF_DASHBOARD_INTERNAL_BUFFER_POINTS', 1000),
            'poll_interval_ms' => 1000,
            'poll_batch_limit' => 120,
            'realtime_enabled' => env('LEAF_DASHBOARD_REALTIME_TELEMETRY', true),
            'polling_fallback_enabled' => env('LEAF_DASHBOARD_TELEMETRY_POLLING_FALLBACK', false),
            'debug' => env('LEAF_DASHBOARD_CHART_DEBUG', false),
        ],
    ],
    'camera' => [
        // The ESP32-S3-CAM registers its LAN address via device heartbeat.
        'stream_scheme' => env('LEAF_CAMERA_STREAM_SCHEME', 'http'),
        'stream_port' => (int) env('LEAF_CAMERA_STREAM_PORT', 81),
        'stream_path' => env('LEAF_CAMERA_STREAM_PATH', '/stream'),
        'audio_port' => (int) env('LEAF_CAMERA_AUDIO_PORT', 82),
        'audio_path' => env('LEAF_CAMERA_AUDIO_PATH', '/audio'),
        // Cloud relay: the camera keeps an outbound WSS connection to this
        // VPS relay; the dashboard serves /camera/stream and /camera/audio
        // through it instead of exposing the camera's LAN IP.
        'relay_url' => env('LEAF_CAMERA_RELAY_URL', 'https://projectleaf.tech'),
        // Shared secret between Laravel (token issuer) and the relay (validator).
        'relay_jwt_secret' => env('LEAF_CAMERA_RELAY_JWT_SECRET'),
        // Relay token TTL in seconds.
        'relay_token_ttl' => (int) env('LEAF_CAMERA_RELAY_TOKEN_TTL', 300),
    ],
    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => (int) env('MQTT_PORT', 1883),
        'client_id' => env('MQTT_CLIENT_ID', 'leaf-mqtt-subscriber'),
        'qos' => (int) env('MQTT_QOS', 0),
        'username' => env('MQTT_USERNAME'),
        'password' => env('MQTT_PASSWORD'),
        'use_tls' => env('MQTT_USE_TLS', false),
        'topics' => [
            'telemetry' => env('MQTT_TOPIC_TELEMETRY', 'leaf/devices/+/telemetry'),
            'status' => env('MQTT_TOPIC_STATUS', 'leaf/devices/+/status'),
            'results' => env('MQTT_TOPIC_RESULTS', 'leaf/devices/+/results'),
            'commands' => env('MQTT_TOPIC_COMMANDS', 'leaf/devices/{device_id}/commands'),
            'settings' => env('MQTT_TOPIC_SETTINGS', 'leaf/devices/{device_id}/settings'),
        ],
    ],
    'simulation' => [
        'device_token' => env('LEAF_SIMULATION_DEVICE_TOKEN'),
        'device_tokens' => env('LEAF_SIMULATION_DEVICE_TOKENS'),
    ],
];
