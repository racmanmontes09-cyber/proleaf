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

