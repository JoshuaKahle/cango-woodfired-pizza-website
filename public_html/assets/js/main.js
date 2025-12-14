// Main JS file
console.log('Cango Pizza Loaded');

document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.js-map-link');
    if (!links || links.length === 0) return;

    const ua = navigator.userAgent || '';
    const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
    const isAndroid = /Android/.test(ua);

    links.forEach(function (a) {
        const q = (a.getAttribute('data-map-query') || a.textContent || '').trim();
        const enc = encodeURIComponent(q);

        if (isIOS) {
            a.href = 'https://maps.apple.com/?q=' + enc;
            a.target = '_blank';
            a.rel = 'noopener';
            return;
        }

        if (isAndroid) {
            a.href = 'geo:0,0?q=' + enc;
            a.removeAttribute('target');
            a.removeAttribute('rel');
            return;
        }

        a.href = 'https://www.google.com/maps/search/?api=1&query=' + enc;
        a.target = '_blank';
        a.rel = 'noopener';
    });
});
