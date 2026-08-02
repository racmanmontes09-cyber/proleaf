import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    forceTLS: true
});

if (window.Echo?.connector?.pusher?.connection) {
    const pusherConnection = window.Echo.connector.pusher.connection;
    pusherConnection.bind('connected', () => {
        console.log('Echo connected to Pusher:', pusherConnection.state);
    });
    pusherConnection.bind('error', (error) => {
        console.error('Echo Pusher connection error:', error);
    });
}

function updateDashboardClock() {
    const clockElement = document.getElementById('dashboard-clock-time');
    if (!clockElement) {
        return;
    }

    clockElement.textContent = new Date().toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function updateEsp32Status() {
    const statusDot = document.getElementById('esp32-status-dot');
    const statusLabel = document.getElementById('esp32-status-label');

    if (!statusDot || !statusLabel) {
        return;
    }

    fetch('/dashboard/esp32/status', {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Failed to fetch ESP32 status');
            }
            return response.json();
        })
        .then((json) => {
            const online = Boolean(json.online);
            statusLabel.textContent = json.statusLabel || (online ? 'ESP32 Online' : 'ESP32 Offline');
            statusDot.className = `h-2.5 w-2.5 rounded-full ${online ? 'bg-[#2D6A4F]' : 'bg-rose-500'}`;
        })
        .catch((error) => {
            console.debug('ESP32 status refresh error:', error);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    updateDashboardClock();
    updateEsp32Status();

    setInterval(updateDashboardClock, 1000);
    setInterval(updateEsp32Status, 5000);
});

