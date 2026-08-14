# Local Realtime Telemetry

This local stack keeps the REST telemetry endpoint available for initial history, fallback, and debugging, but the live dashboard path is event-driven:

ESP32-S3 -> MQTT -> Mosquitto -> Laravel `mqtt:subscribe` -> `TelemetryService` -> MySQL -> `TelemetryReceived` -> Laravel Reverb -> Echo -> KPI and ApexCharts incremental append.

## 1. Mosquitto on the Ubuntu LAN host

Install Mosquitto on the Ubuntu laptop that runs Laravel:

```bash
sudo apt update
sudo apt install mosquitto mosquitto-clients
```

For local intranet testing, bind Mosquitto to the LAN and do not expose port `1883` publicly. A minimal local listener can be placed in `/etc/mosquitto/conf.d/leaf-local.conf`:

```conf
listener 1883 0.0.0.0
allow_anonymous true
```

Keep this listener behind your local firewall/router. It is intended for the same Wi-Fi/LAN only.

Restart and verify:

```bash
sudo systemctl restart mosquitto
sudo systemctl status mosquitto
mosquitto_sub -h 127.0.0.1 -t 'devices/+/telemetry' -v
```

Find the Ubuntu laptop LAN IP and use that value in ESP32 firmware configuration:

```bash
hostname -I | awk '{print $1}'
```

The ESP32 must use that LAN IP, not `127.0.0.1`.

## 2. Laravel environment

Set the local backend values in `backend/.env`:

```dotenv
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

REVERB_APP_ID=local-leaf
REVERB_APP_KEY=replace-with-local-key
REVERB_APP_SECRET=replace-with-local-secret

# Laravel publishes to Reverb from this same Ubuntu machine.
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Reverb accepts browser WebSocket connections on the LAN.
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_BROADCAST_CONNECTION=reverb
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"

# Leave empty to use the dashboard page host, or set to the Ubuntu LAN IP.
VITE_REVERB_HOST=
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_TOPIC=devices/+/telemetry
MQTT_CLIENT_ID=leaf-mqtt-subscriber

LEAF_DASHBOARD_REALTIME_TELEMETRY=true
LEAF_DASHBOARD_TELEMETRY_POLLING_FALLBACK=false
```

If the dashboard is served at `http://<ubuntu-lan-ip>:8000`, leaving `VITE_REVERB_HOST` empty makes Echo connect back to that same LAN host. If the browser needs a different reachable host, set `VITE_REVERB_HOST` explicitly. Do not commit real keys or secrets.

## 3. Start the local stack

From `backend/`:

```bash
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work --queue=broadcasts,default
php artisan mqtt:subscribe
php artisan reverb:start --host=0.0.0.0 --port=8080
npm run dev -- --host 0.0.0.0
```

Run each long-lived process in its own terminal or under a process supervisor. The queue worker is required because `TelemetryReceived` broadcasts on the `broadcasts` queue.

## 4. ESP32 firmware LAN config

Build/upload the firmware with the laptop LAN IP as the MQTT broker:

```bash
export LEAF_WIFI_SSID='your-wifi'
export LEAF_WIFI_PASSWORD='your-password'
export LEAF_DEVICE_ID='LEAF-MQTT-01'
export LEAF_DEVICE_NAME='Project LEAF Controller'
export LEAF_DEVICE_TOKEN='existing-rest-fallback-token'
export LEAF_API_BASE_URL='http://<ubuntu-lan-ip>:8000'
export LEAF_MQTT_HOST='<ubuntu-lan-ip>'
export LEAF_MQTT_CLIENT_ID='leaf-esp32-01'
export LEAF_MQTT_TELEMETRY_TOPIC='devices/{device_id}/telemetry'

pio run -d ../proleaf -e 4d_systems_esp32s3_gen4_r8n16 --target upload
```

`LEAF_API_BASE_URL` remains available for heartbeat, commands, REST fallback, and queued telemetry fallback. `LEAF_MQTT_HOST` is the active telemetry path.

## 5. Validation checklist

Publish a single test message:

```bash
mosquitto_pub -h 127.0.0.1 -t devices/LEAF-MQTT-01/telemetry -m '{"ph":6.4,"water_temperature":22.1,"measured_at":"2026-07-25T12:00:01Z"}'
```

Verify:

- `php artisan mqtt:subscribe` logs no handler errors.
- MySQL contains one new `telemetries` row.
- The browser receives one `.TelemetryReceived` Echo event on `private-devices.{id}.telemetry`.
- KPI values change without page reload or Livewire telemetry refresh.
- ApexCharts appends one point with the event `timestamp`.
- Re-publishing the same payload with the same `measured_at` creates no duplicate chart point.
- Device A events do not update Device B because the dashboard filters by internal `device_id`.

## 6. Runtime behavior

On dashboard load, the browser requests historical readings once from `/dashboard/telemetry/readings`, renders the existing chart, then subscribes to `devices.{id}.telemetry` over Echo/Reverb. After subscription, new telemetry is delivered by WebSocket and appended with ApexCharts `appendData()`. The 1-second telemetry polling loop remains available only when Echo is unavailable or `LEAF_DASHBOARD_TELEMETRY_POLLING_FALLBACK=true`; it is stopped while the WebSocket subscription is connected.

The chart uses the telemetry `timestamp`/`measured_at` supplied by Laravel, not the browser receive time. Laravel stores telemetry timestamps in UTC and serializes broadcast timestamps as UTC ISO-8601 values.
