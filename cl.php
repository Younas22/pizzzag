<?php
// No caching on this page itself
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refreshing PizzaG...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a0a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: sans-serif;
            color: #f5f5f5;
            gap: 20px;
        }
        img { width: 100px; height: auto; }
        .msg { font-size: 16px; color: #aaa; }
        .bar-wrap {
            width: 220px;
            height: 6px;
            background: #222;
            border-radius: 10px;
            overflow: hidden;
        }
        .bar {
            height: 100%;
            width: 0%;
            background: #FF671C;
            border-radius: 10px;
            transition: width 0.1s linear;
        }
    </style>
</head>
<body>
    <img src="icons/main-pizzag-logo.png" alt="PizzaG">
    <p class="msg" id="msg">Cache clear ho raha hai...</p>
    <div class="bar-wrap"><div class="bar" id="bar"></div></div>

    <script>
        const bar = document.getElementById('bar');
        const msg = document.getElementById('msg');
        let progress = 0;

        function setProgress(p, text) {
            progress = p;
            bar.style.width = p + '%';
            if (text) msg.textContent = text;
        }

        async function clearEverything() {
            setProgress(10, 'Service Worker band kar raha hai...');

            // 1. Unregister all service workers
            if ('serviceWorker' in navigator) {
                const regs = await navigator.serviceWorker.getRegistrations();
                for (const reg of regs) await reg.unregister();
            }

            setProgress(40, 'Cache delete ho raha hai...');

            // 2. Delete all caches
            if ('caches' in window) {
                const keys = await caches.keys();
                await Promise.all(keys.map(k => caches.delete(k)));
            }

            setProgress(70, 'LocalStorage reset...');

            // 3. Clear PWA install flags so banners reset too
            localStorage.removeItem('pwa-installed');
            localStorage.removeItem('pwa-banner-dismissed');
            localStorage.removeItem('ios-banner-dismissed');

            setProgress(90, 'Redirect ho raha hai...');

            // 4. Redirect to home with cache-bust query
            await new Promise(r => setTimeout(r, 400));
            setProgress(100, 'Done!');
            await new Promise(r => setTimeout(r, 300));

            window.location.replace('./?' + Date.now());
        }

        clearEverything();
    </script>
</body>
</html>
