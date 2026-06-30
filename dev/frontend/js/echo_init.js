/**
 * echo_init.js — Inicialização do WebSocket (Laravel Reverb via Pusher/Echo)
 * Carregado globalmente pelo layout app.blade.php
 */
(function () {
    try {
        window.Pusher = window.Pusher || (typeof Pusher !== 'undefined' ? Pusher : null);
        if (typeof Echo !== 'undefined' && window.__REVERB_APP_KEY__) {
            window.EchoApp = new Echo({
                broadcaster: 'reverb',
                key: window.__REVERB_APP_KEY__,
                wsHost: window.location.hostname,
                wsPort: 80,
                wssPort: 443,
                forceTLS: (window.location.protocol === 'https:'),
                enabledTransports: ['ws', 'wss'],
            });
        }
    } catch (e) {
        console.warn('WebSockets offline (talvez bloqueado por AdBlock). O sistema continuará funcionando.', e);
    }
}());
