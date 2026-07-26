#!/bin/bash
cd /home/u575872597/domains/proleaf.pudoceast.com/public_html

echo "🔧 Fixing app.js..."

# Enable Node.js
source /opt/alt/alt-nodejs20/enable

cat > resources/js/app.js << 'APPJS'
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
});
APPJS

# Clear and rebuild
echo "Clearing caches..."
rm -rf public/build node_modules/.vite bootstrap/cache/*
/opt/alt/php84/usr/bin/php artisan config:clear
/opt/alt/php84/usr/bin/php artisan cache:clear
/opt/alt/php84/usr/bin/php artisan view:clear

echo "Building..."
npm run build

echo "Broadcasting setup complete."

echo ""
echo "✅ FIX COMPLETE!"
echo "Now:"
echo "1. Hard refresh: Ctrl+Shift+R"
echo "2. Check console for: 'Echo initialized with ws://'"
echo "3. Check Network → WS for the connection"
