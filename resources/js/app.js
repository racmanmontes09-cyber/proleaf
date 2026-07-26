import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Create Echo instance configured for Pusher
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 443,
    wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
    forceTLS: true,
    encrypted: true,
    enabledTransports: ['wss', 'ws'],
});

window.Echo = echo;

if (echo.connector) console.log('✅ Connector configured for pusher');

console.log('✅ Echo initialized for pusher');

window.__echo_config = echo.options;
