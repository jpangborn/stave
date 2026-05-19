if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .catch((error) => {
                console.error('Stave service worker registration failed', error);
            });
    });
}
