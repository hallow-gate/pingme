// register-sw.js - Enhanced with background support
(function() {
    'use strict';
    
    async function initServiceWorker() {
        console.log('Initializing Service Worker with background support...');
        
        if (!('serviceWorker' in navigator)) {
            console.error('Service Worker not supported');
            return;
        }
        
        if (!('Notification' in window)) {
            console.error('Notifications not supported');
            return;
        }
        
        // Request notification permission
        async function requestNotificationPermission() {
            try {
                const permission = await Notification.requestPermission();
                console.log('Notification permission:', permission);
                
                if (permission === 'granted') {
                    await registerAndSetup();
                } else {
                    showPermissionDenied();
                }
            } catch (error) {
                console.error('Error requesting permission:', error);
            }
        }
        
        function showPermissionDenied() {
            const message = document.createElement('div');
            message.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #ff9800;
                color: white;
                padding: 12px 20px;
                border-radius: 5px;
                z-index: 10000;
                font-family: Arial, sans-serif;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            message.innerHTML = '🔔 Please enable notifications in browser settings to receive alerts';
            document.body.appendChild(message);
            setTimeout(() => message.remove(), 5000);
        }
        
        async function registerAndSetup() {
            try {
                // Register service worker
                const registration = await navigator.serviceWorker.register('/sw.js', { 
                    scope: '/' 
                });
                console.log('Service Worker registered:', registration);
                
                // Wait for activation
                if (registration.waiting) {
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                }
                
                // Set up background sync if available
                if ('sync' in registration) {
                    try {
                        await registration.sync.register('sync-notifications');
                        console.log('Background sync registered');
                    } catch (error) {
                        console.log('Background sync failed:', error);
                    }
                }
                
                // Set up periodic sync if available (Chrome 80+)
                if ('periodicSync' in registration) {
                    try {
                        const status = await navigator.permissions.query({
                            name: 'periodic-background-sync',
                        });
                        
                        if (status.state === 'granted') {
                            await registration.periodicSync.register('periodic-notifications', {
                                minInterval: 15 * 60 * 1000 // 15 minutes
                            });
                            console.log('Periodic background sync registered');
                        }
                    } catch (error) {
                        console.log('Periodic sync not supported:', error);
                    }
                }
                
                // Keep service worker alive
                keepServiceWorkerAlive(registration);
                
                // Show test notification
                await registration.showNotification('✅ Notifications Enabled', {
                    body: 'You will receive notifications even when browser is in background',
                    icon: '/favicon.ico',
                    tag: 'welcome',
                    requireInteraction: false
                });
                
                // Set up message listener for background checks
                setupBackgroundCheckListener(registration);
                
                // Add test controls
                addTestControls(registration);
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
        
        // Keep service worker alive
        function keepServiceWorkerAlive(registration) {
            // Send heartbeat every 30 seconds
            setInterval(() => {
                if (registration.active) {
                    registration.active.postMessage({ type: 'HEARTBEAT' });
                    console.log('Heartbeat sent to service worker');
                }
            }, 30000);
            
            // Re-register sync if needed
            setInterval(async () => {
                if (registration.active && 'sync' in registration) {
                    try {
                        await registration.sync.register('sync-notifications');
                        console.log('Background sync re-registered');
                    } catch (error) {
                        console.log('Failed to re-register sync:', error);
                    }
                }
            }, 60 * 60 * 1000); // Every hour
        }
        
        // Set up background check listener
        function setupBackgroundCheckListener(registration) {
            // Listen for visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    console.log('Page hidden - service worker continues to run');
                } else {
                    console.log('Page visible - triggering immediate check');
                    if (registration.active) {
                        registration.active.postMessage({ type: 'FORCE_CHECK' });
                    }
                }
            });
            
            // Set up beforeunload to ensure sync is registered
            window.addEventListener('beforeunload', () => {
                if (registration.active && 'sync' in registration) {
                    registration.sync.register('sync-notifications');
                }
            });
        }
        
        // Add test controls to page
        function addTestControls(registration) {
            const controlPanel = document.createElement('div');
            controlPanel.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 20px;
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 10px;
                border-radius: 5px;
                z-index: 10000;
                font-size: 12px;
                font-family: monospace;
                display: flex;
                gap: 10px;
            `;
            
            const testBtn = document.createElement('button');
            testBtn.textContent = 'Test Notification';
            testBtn.style.cssText = `
                background: #4CAF50;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                cursor: pointer;
            `;
            testBtn.onclick = async () => {
                await registration.showNotification('Test Notification', {
                    body: 'Background notifications are working!',
                    icon: '/favicon.ico',
                    tag: 'test',
                    requireInteraction: false
                });
            };
            
            const checkBtn = document.createElement('button');
            checkBtn.textContent = 'Check Now';
            checkBtn.style.cssText = `
                background: #2196F3;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                cursor: pointer;
            `;
            checkBtn.onclick = () => {
                if (registration.active) {
                    registration.active.postMessage({ type: 'FORCE_CHECK' });
                    console.log('Manual check triggered');
                }
            };
            
            controlPanel.appendChild(testBtn);
            controlPanel.appendChild(checkBtn);
            document.body.appendChild(controlPanel);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                if (controlPanel.parentNode) {
                    controlPanel.style.opacity = '0.5';
                }
            }, 10000);
        }
        
        // Start the process
        if (Notification.permission === 'granted') {
            await registerAndSetup();
        } else if (Notification.permission === 'default') {
            // Wait for user interaction
            const clickHandler = async () => {
                await requestNotificationPermission();
                document.removeEventListener('click', clickHandler);
            };
            document.addEventListener('click', clickHandler);
            
            // Show prompt
            showNotificationPrompt();
        }
        
        function showNotificationPrompt() {
            const prompt = document.createElement('div');
            prompt.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px;
                border-radius: 10px;
                z-index: 10000;
                text-align: center;
                box-shadow: 0 4px 15px rgba(0,0,0,0.3);
                animation: slideUp 0.3s ease-out;
            `;
            prompt.innerHTML = `
                <strong>🔔 Stay Updated!</strong><br>
                Get notifications for new messages and friend requests<br>
                <button style="margin-top: 10px; padding: 8px 20px; background: white; color: #667eea; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Enable Notifications
                </button>
            `;
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideUp {
                    from {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(style);
            
            prompt.querySelector('button').onclick = async () => {
                await requestNotificationPermission();
                prompt.remove();
            };
            
            document.body.appendChild(prompt);
            
            // Auto remove after 15 seconds
            setTimeout(() => {
                if (prompt.parentNode) prompt.remove();
            }, 15000);
        }
    }
    
    // Start when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServiceWorker);
    } else {
        initServiceWorker();
    }
})();