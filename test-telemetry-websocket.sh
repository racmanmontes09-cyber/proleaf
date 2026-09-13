#!/usr/bin/env bash
#
# test-telemetry-websocket.sh — End-to-end WebSocket telemetry pipeline test
#
# Usage:
#   ./test-telemetry-websocket.sh                  # 10 payloads, 2s interval
#   ./test-telemetry-websocket.sh --count 30       # 30 payloads
#   ./test-telemetry-websocket.sh --count 0        # run until Ctrl+C
#
set -euo pipefail

APP_URL="${APP_URL:-http://127.0.0.1:8000}"
REVERB_HOST="${VITE_REVERB_HOST:-${REVERB_HOST:-127.0.0.1}}"
REVERB_PORT="${VITE_REVERB_PORT:-${REVERB_PORT:-8080}}"
REVERB_SCHEME="${VITE_REVERB_SCHEME:-${REVERB_SCHEME:-https}}"
COUNT="${COUNT:-10}"
INTERVAL="${INTERVAL:-2}"
DEVICE_TOKEN="${LEAF_DEVICE_TOKEN:-}"

while [[ $# -gt 0 ]]; do
    case "$1" in
        --count)       COUNT="$2"; shift 2 ;;
        --interval)    INTERVAL="$2"; shift 2 ;;
        --token)       DEVICE_TOKEN="$2"; shift 2 ;;
        --url)         APP_URL="$2"; shift 2 ;;
        --reverb-host) REVERB_HOST="$2"; shift 2 ;;
        --reverb-port) REVERB_PORT="$2"; shift 2 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

header() { echo -e "\n${GREEN}${BOLD}  $1  ${NC}"; }
info()   { echo -e "  ${GREEN}✓${NC} $1"; }
warn()   { echo -e "  ${YELLOW}⚠${NC} $1"; }
fail()   { echo -e "  ${RED}✗${NC} $1"; }
step()   { echo -e "\n${YELLOW}${BOLD}── $1 ──${NC}"; }

print_browser_instructions() {
    step "Browser Verification"
    local ws_url="${REVERB_SCHEME}://${REVERB_HOST}:${REVERB_PORT}"
    echo "  1. Open the dashboard:"
    echo -e "     ${BOLD}${APP_URL}/dashboard${NC}"
    echo ""
    echo "  2. Open browser DevTools > Console tab."
    echo ""
    echo "  3. Look for these log messages:"
    echo "       [Realtime] Echo connecting ..."
    echo "       [Realtime] Echo connected ..."
    echo "       [Realtime] subscribing to devices.{id}.telemetry"
    echo "       [Realtime] subscribed to devices.{id}.telemetry"
    echo ""
    echo "  4. Verify WebSocket in DevTools > Network > WS:"
    echo "       URL: ${ws_url}/app/{REVERB_APP_KEY}"
    echo "       Status: 101 Switching Protocols"
    echo "       (403 = auth issue | 404 = Reverb not running)"
    echo ""
    echo "  5. Live data verification:"
    echo "       - KPI cards pulse on new readings"
    echo "       - Charts append new data points"
    echo "       - 'Last Updated' shows 'just now'"
    echo ""
    echo "  6. Quick Reverb health check:"
    echo -e "     ${BOLD}curl -s -o /dev/null -w '%{http_code}' ${ws_url}${NC}"
    echo "     Expected: 404 (normal -- Reverb serves WebSocket upgrades only)"
    echo ""
}

SENT=0; OK=0; FAIL_COUNT=0; START_TIME=$(date +%s)

cleanup() {
    echo ""
    header "RESULTS"
    echo "  Sent:      ${SENT}"
    echo "  OK:        ${OK}"
    echo "  Failed:    ${FAIL_COUNT}"
    ELAPSED=$(($(date +%s) - START_TIME))
    echo "  Elapsed:   ${ELAPSED}s"
    echo ""
    print_browser_instructions
    exit 0
}
trap cleanup SIGINT SIGTERM

# Resolve token
if [[ -z "$DEVICE_TOKEN" ]]; then
    for env_file in .env ../.env; do
        if [[ -f "$env_file" ]]; then
            DEVICE_TOKEN=$(grep -oP 'LEAF_DEVICE_TOKEN="\K[^"]+' "$env_file" 2>/dev/null || true)
            if [[ -n "$DEVICE_TOKEN" ]]; then break; fi
        fi
    done
fi

if [[ -z "$DEVICE_TOKEN" ]]; then
    echo -e "\n${RED}ERROR: No device token found.${NC}"
    echo "  Set LEAF_DEVICE_TOKEN environment variable or pass --token."
    exit 1
fi

echo ""
echo -e "${GREEN}${BOLD}  PROJECT L.E.A.F.${NC} WebSocket Telemetry Pipeline Test"
echo "  --------------------------------------------------------"

# Pre-flight
step "Pre-flight Checks"

if (echo > /dev/tcp/"$REVERB_HOST"/"$REVERB_PORT") 2>/dev/null; then
    info "Reverb port ${REVERB_PORT} open on ${REVERB_HOST}"
else
    warn "Reverb port ${REVERB_PORT} not reachable on ${REVERB_HOST}"
fi

HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' "$APP_URL/" 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" != "000" ]]; then
    info "API reachable at ${APP_URL} (HTTP ${HTTP_CODE})"
else
    fail "API unreachable at ${APP_URL}"
    exit 1
fi

# Single-shot verification
step "Single-Shot Verification"

NOW=$(date -u +%Y-%m-%dT%H:%M:%S.000000Z)
RESPONSE=$(curl -s -w "\n%{http_code}" \
    -X POST "${APP_URL}/api/devices/telemetry" \
    -H "Authorization: Bearer ${DEVICE_TOKEN}" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{
        \"air_temperature\": 24.5,
        \"humidity\": 68,
        \"water_temperature\": 20.5,
        \"ph\": 6.3,
        \"ec\": 1.42,
        \"water_flow\": 1.3,
        \"water_level\": 62,
        \"measured_at\": \"${NOW}\",
        \"sequence_number\": 0,
        \"firmware_version\": \"leaf-test-stream\",
        \"signal_strength\": -58,
        \"battery_voltage\": 3.82,
        \"payload_version\": 1
    }" 2>&1)

STATUS=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | sed '$d')

if [[ "$STATUS" -ge 200 && "$STATUS" -lt 300 ]]; then
    info "[${STATUS}] Telemetry accepted -- broadcast event dispatched"
else
    fail "[${STATUS}] Request failed: ${BODY}"
    exit 1
fi

# Stream
step "Streaming Mock Telemetry"
echo "  Interval: ${INTERVAL}s | Count: $([ "$COUNT" -eq 0 ] && echo "unlimited" || echo "$COUNT")"
echo ""

while true; do
    if [[ "$COUNT" -gt 0 && "$SENT" -ge "$COUNT" ]]; then break; fi

    SEQ=$((SENT + 1))
    NOW=$(date -u +%Y-%m-%dT%H:%M:%S.000000Z)
    T=$(echo "24.0 + 3.0 * sin($SEQ * 0.3)" | bc -l 2>/dev/null || echo "24.5")
    PH=$(echo "6.2 + 0.4 * sin($SEQ * 0.25)" | bc -l 2>/dev/null || echo "6.3")
    EC=$(echo "1.4 + 0.3 * sin($SEQ * 0.18)" | bc -l 2>/dev/null || echo "1.42")
    WL=$(echo "60.0 + 15.0 * sin($SEQ * 0.1)" | bc -l 2>/dev/null || echo "62")
    WF=$(echo "1.2 + 0.5 * sin($SEQ * 0.4)" | bc -l 2>/dev/null || echo "1.3")

    RESP=$(curl -s -w "\n%{http_code}" \
        -X POST "${APP_URL}/api/devices/telemetry" \
        -H "Authorization: Bearer ${DEVICE_TOKEN}" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"air_temperature\": ${T},
            \"humidity\": 68,
            \"water_temperature\": 20.5,
            \"ph\": ${PH},
            \"ec\": ${EC},
            \"water_flow\": ${WF},
            \"water_level\": ${WL},
            \"measured_at\": \"${NOW}\",
            \"sequence_number\": ${SEQ},
            \"firmware_version\": \"leaf-test-stream\",
            \"signal_strength\": $((- 55 - RANDOM % 20)),
            \"battery_voltage\": 3.82,
            \"payload_version\": 1
        }" 2>&1)

    STATUS=$(echo "$RESP" | tail -1)
    SENT=$((SENT + 1))

    if [[ "$STATUS" -ge 200 && "$STATUS" -lt 300 ]]; then
        OK=$((OK + 1))
        printf "  \033[0;32m#\033[0m%-3d [%s]  air=%s°C  pH=%s  EC=%s  level=%s%%  flow=%s L/min\n" \
            "$SEQ" "$STATUS" "$T" "$PH" "$EC" "$WL" "$WF"
    else
        FAIL_COUNT=$((FAIL_COUNT + 1))
        printf "  \033[0;31m#\033[0m%-3d [%s]  FAILED\n" "$SEQ" "$STATUS"
    fi

    if [[ "$COUNT" -eq 0 || "$SENT" -lt "$COUNT" ]]; then
        sleep "$INTERVAL"
    fi
done

cleanup
