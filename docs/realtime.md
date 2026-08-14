# Realtime Telemetry

Project L.E.A.F. keeps the REST telemetry API for initial history, fallback, and debugging. The active local live-dashboard path is:

ESP32-S3 -> MQTT -> Mosquitto -> Laravel `mqtt:subscribe` -> `TelemetryService` -> MySQL -> `TelemetryReceived` -> Laravel Reverb -> Echo -> KPI update + ApexCharts incremental append.

Use `docs/local-realtime-telemetry.md` for the complete local intranet runbook, including Mosquitto setup, Reverb environment variables, ESP32 LAN configuration, and validation steps.

Key runtime rules:

- The ESP32 publishes MQTT to the Ubuntu laptop LAN IP, never `127.0.0.1`.
- Laravel connects to local Mosquitto with `MQTT_HOST=127.0.0.1` when Mosquitto runs on the same laptop.
- Laravel publishes broadcasts to local Reverb with `REVERB_HOST=127.0.0.1`.
- Browser Echo connects to `VITE_REVERB_HOST` when set; otherwise it uses the dashboard page host, which works for LAN testing when the dashboard is opened through the laptop LAN IP.
- The dashboard loads initial history once from `/dashboard/telemetry/readings`, then receives new telemetry over WebSockets.
- Telemetry polling is a fallback path only while Echo/Reverb is unavailable.
