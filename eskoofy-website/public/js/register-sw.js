// Eskoofy PWA service-worker registration.
// Only registers on HTTPS (or localhost) so browsers don't complain on dev HTTP.
(function () {
    if (!('serviceWorker' in navigator)) return;

    var allowed = location.protocol === 'https:' ||
        ['localhost', '127.0.0.1'].indexOf(location.hostname) !== -1;
    if (!allowed) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function (err) {
            console.error('Service worker registration failed:', err);
        });
    });
})();