#!/bin/bash
cd /home/u575872597/domains/proleaf.pudoceast.com/public_html

echo "🔧 Fixing app.js..."

# Enable Node.js
source /opt/alt/alt-nodejs20/enable

cat > resources/js/app.js << 'APPJS'
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
APPJS

# Clear and rebuild
echo "Clearing caches..."
rm -rf public/build node_modules/.vite bootstrap/cache/*
/opt/alt/php84/usr/bin/php artisan config:clear
/opt/alt/php84/usr/bin/php artisan cache:clear
/opt/alt/php84/usr/bin/php artisan view:clear

echo "Building..."
npm run build

echo "Restarting Reverb..."
pkill -f reverb
nohup /opt/alt/php84/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &

echo ""
echo "✅ FIX COMPLETE!"
echo "Now:"
echo "1. Hard refresh: Ctrl+Shift+R"
echo "2. Check console for: 'Echo initialized with ws://'"
echo "3. Check Network → WS for the connection"
