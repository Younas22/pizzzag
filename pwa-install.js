/**
 * PizzaG - PWA Install Handler
 * Custom "Add to Home Screen" functionality
 */

(function() {
    'use strict';

    // Store the deferred prompt event
    let deferredPrompt = null;

    // Track if app is already installed
    let isInstalled = false;

    // DOM Elements (will be set after DOM loads)
    let installBanner = null;
    let installButton = null;
    let iosBanner = null;
    let closeBannerBtn = null;
    let closeIosBannerBtn = null;

    /**
     * Initialize PWA Install functionality
     */
    function init() {
        // Get DOM elements
        installBanner = document.getElementById('pwa-install-banner');
        installButton = document.getElementById('pwa-install-btn');
        iosBanner = document.getElementById('ios-install-banner');
        closeBannerBtn = document.getElementById('close-install-banner');
        closeIosBannerBtn = document.getElementById('close-ios-banner');

        if (!installBanner || !installButton) {
            console.warn('[PWA Install] Install banner elements not found');
            return;
        }

        // Check if already installed
        checkIfInstalled();

        // Set up event listeners
        setupEventListeners();

        // Register service worker
        registerServiceWorker();

        // Check for iOS
        if (isIOS() && !isInStandaloneMode()) {
            showIOSInstallBanner();
        }
    }

    /**
     * Register the service worker
     */
    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/service-worker.js')
                    .then((registration) => {
                        console.log('[PWA] Service Worker registered:', registration.scope);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New content available, you could show an update prompt here
                                    console.log('[PWA] New content available');
                                }
                            });
                        });
                    })
                    .catch((error) => {
                        console.error('[PWA] Service Worker registration failed:', error);
                    });
            });
        }
    }

    /**
     * Set up all event listeners
     */
    function setupEventListeners() {
        // Capture the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

        // Handle successful installation
        window.addEventListener('appinstalled', handleAppInstalled);

        // Install button click
        if (installButton) {
            installButton.addEventListener('click', handleInstallClick);
        }

        // Close banner button
        if (closeBannerBtn) {
            closeBannerBtn.addEventListener('click', hideInstallBanner);
        }

        // Close iOS banner button
        if (closeIosBannerBtn) {
            closeIosBannerBtn.addEventListener('click', hideIOSInstallBanner);
        }
    }

    /**
     * Handle the beforeinstallprompt event
     * This event fires when the browser determines the app is installable
     */
    function handleBeforeInstallPrompt(event) {
        console.log('[PWA Install] beforeinstallprompt event fired');

        // Prevent the default browser prompt
        event.preventDefault();

        // Store the event for later use
        deferredPrompt = event;

        // Show our custom install banner
        showInstallBanner();
    }

    /**
     * Handle the app installed event
     */
    function handleAppInstalled(event) {
        console.log('[PWA Install] App was installed');

        // Clear the deferred prompt
        deferredPrompt = null;

        // Mark as installed
        isInstalled = true;

        // Save to localStorage
        localStorage.setItem('pwa-installed', 'true');

        // Hide the install banner
        hideInstallBanner();

        // Track install in database
        trackInstallToDatabase();

        // Show success message (optional)
        showInstallSuccess();
    }

    /**
     * Track PWA install to database
     */
    function trackInstallToDatabase() {
        fetch('/api/pwa_install.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'install'
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('[PWA Install] Install tracked:', data);
        })
        .catch(error => {
            console.error('[PWA Install] Failed to track install:', error);
        });
    }

    /**
     * Handle install button click
     */
    async function handleInstallClick() {
        console.log('[PWA Install] Install button clicked');

        if (!deferredPrompt) {
            console.warn('[PWA Install] No deferred prompt available');
            return;
        }

        // Show the browser's install prompt
        deferredPrompt.prompt();

        // Wait for the user's response
        const { outcome } = await deferredPrompt.userChoice;

        console.log('[PWA Install] User choice:', outcome);

        if (outcome === 'accepted') {
            console.log('[PWA Install] User accepted the install prompt');
        } else {
            console.log('[PWA Install] User dismissed the install prompt');
        }

        // Clear the deferred prompt - it can only be used once
        deferredPrompt = null;
    }

    /**
     * Show the custom install banner
     */
    function showInstallBanner() {
        if (!installBanner || isInstalled) return;

        // Check if user has dismissed the banner recently
        const dismissedTime = localStorage.getItem('pwa-banner-dismissed');
        if (dismissedTime) {
            const hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
            // Don't show again for 24 hours after dismissal
            if (hoursSinceDismissed < 24) {
                return;
            }
        }

        installBanner.classList.remove('hidden');
        installBanner.classList.add('pwa-banner-animate');
    }

    /**
     * Hide the install banner
     */
    function hideInstallBanner() {
        if (!installBanner) return;

        installBanner.classList.add('hidden');
        installBanner.classList.remove('pwa-banner-animate');

        // Remember dismissal time
        localStorage.setItem('pwa-banner-dismissed', Date.now().toString());
    }

    /**
     * Check if app is already installed
     */
    function checkIfInstalled() {
        // Check localStorage
        if (localStorage.getItem('pwa-installed') === 'true') {
            isInstalled = true;
            return;
        }

        // Check if running in standalone mode (already installed)
        if (isInStandaloneMode()) {
            isInstalled = true;
            localStorage.setItem('pwa-installed', 'true');
        }
    }

    /**
     * Check if running in standalone mode (installed PWA)
     */
    function isInStandaloneMode() {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://');
    }

    /**
     * Check if device is iOS
     */
    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }

    /**
     * Show iOS-specific install instructions
     */
    function showIOSInstallBanner() {
        if (!iosBanner || isInstalled) return;

        // Check if user has dismissed the iOS banner recently
        const dismissedTime = localStorage.getItem('ios-banner-dismissed');
        if (dismissedTime) {
            const hoursSinceDismissed = (Date.now() - parseInt(dismissedTime)) / (1000 * 60 * 60);
            if (hoursSinceDismissed < 24) {
                return;
            }
        }

        iosBanner.classList.remove('hidden');
        iosBanner.classList.add('pwa-banner-animate');
    }

    /**
     * Hide iOS install banner
     */
    function hideIOSInstallBanner() {
        if (!iosBanner) return;

        iosBanner.classList.add('hidden');
        iosBanner.classList.remove('pwa-banner-animate');

        // Remember dismissal time
        localStorage.setItem('ios-banner-dismissed', Date.now().toString());
    }

    /**
     * Show installation success message
     */
    function showInstallSuccess() {
        // Create a toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2';
        toast.style.cssText = 'background:#FF671C; box-shadow:0 4px 20px rgba(255,103,28,0.4); border:1px solid rgba(255,103,28,0.6);';
        toast.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>PizzaG installed successfully!</span>
        `;

        document.body.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose functions globally for debugging
    window.PWAInstall = {
        showBanner: showInstallBanner,
        hideBanner: hideInstallBanner,
        install: handleInstallClick,
        isInstalled: () => isInstalled,
        isStandalone: isInStandaloneMode
    };

})();
