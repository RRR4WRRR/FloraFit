// notifications.js - COMPLETE SYSTEM WITH SOUND! 🎵
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.audioContext = null;
        this.init();
    }
    
    init() {
        // Create notifications container
        const container = document.createElement('div');
        container.id = 'notifications-container';
        container.style.cssText = `
            position: fixed; top: 140px; right: 20px; z-index: 10000;
            display: flex; flex-direction: column; gap: 12px; max-width: 380px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
        
        // Load notification sound
        this.loadNotificationSound();
        
        // Global click to dismiss
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#notifications-container')) {
                this.clearAll();
            }
        });
    }
    
    loadNotificationSound() {
        try {
            const resumeAudio = () => {
                if (this.audioContext && this.audioContext.state === 'suspended') {
                    this.audioContext.resume();
                }
            };
            
            document.addEventListener('click', resumeAudio, { once: true });
            document.addEventListener('touchstart', resumeAudio, { once: true });
            
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.playBeep = () => this.playNotificationSound();
        } catch(e) {
            console.log('Audio not supported');
        }
    }
    
    playNotificationSound() {
        if (!this.audioContext) return;
        
        try {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(400, this.audioContext.currentTime + 0.2);
            
            gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
            
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + 0.3);
        } catch(e) {}
    }

    // 📱 MOBILE PUSH + SMS for CRITICAL stock
    async sendMobileAlert(message, type, isCritical = false) {
        try {
            // 1. Browser Push Notification
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('🚨 FloraFit Alert', {
                    body: message,
                    icon: '/favicon.ico',
                    badge: '/favicon-32x32.png',
                    vibrate: [200, 100, 200],
                    tag: 'florafit-critical'
                });
            } else if ('Notification' in window && Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
            
            // 2. 🔥 SMS SYSTEM - now using TextBelt via notify_critical_stock.php
            if (isCritical && type === 'danger') {
                const criticalItem = message.match(/CRITICAL[:\s]+(.+?)(?:\s+\$|\.|$)/i)?.[1]?.trim() || 'Stock Alert';
                const stockMatch = message.match(/\$(\d+)\s+left/i);
                const stock = stockMatch ? stockMatch[1] : 0;
                
                await fetch('api/notify_critical_stock.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        critical_items: [{
                            name: criticalItem,
                            stock: stock
                        }]
                    })
                });
            }
            
            // 3. PWA Vibration
            if (navigator.vibrate) {
                navigator.vibrate([200, 100, 200, 100, 200]);
            }
            
        } catch(e) {
            console.log('Mobile alert failed:', e);
        }
    }
    
    showNotification(message, type = 'info', duration = 5000, isCritical = false) {
        const container = document.getElementById('notifications-container');
        if (!container) return;
        
        // 🔥 SOUND + MOBILE ALERT
        this.playNotificationSound();
        if (isCritical || type === 'danger') {
            this.sendMobileAlert(message, type, true);
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: white; border-radius: 12px; padding: 16px 20px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.15); backdrop-filter: blur(10px);
            transform: translateX(400px); opacity: 0; transition: all 0.4s cubic-bezier(0.25,0.8,0.25,1);
            pointer-events: auto; max-width: 380px; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            border-left: 4px solid;
            cursor: pointer; user-select: none;
        `;
        
        // Type styles
        const colors = {
            success: { borderLeft: '#10b981', bg: 'rgba(16,185,129,0.1)' },
            error: { borderLeft: '#ef4444', bg: 'rgba(239,68,68,0.1)' },
            warning: { borderLeft: '#f59e0b', bg: 'rgba(245,158,11,0.1)' },
            danger: { borderLeft: '#dc2626', bg: 'rgba(220,38,38,0.15)' },
            info: { borderLeft: '#3b82f6', bg: 'rgba(59,130,246,0.1)' }
        };
        
        const style = colors[type] || colors.info;
        Object.assign(notification.style, style);
        
        // Icon + Content
        const icon = {
            success: '✅', error: '❌', warning: '⚠️', danger: '🚨', info: 'ℹ️'
        }[type] || 'ℹ️';
        
        notification.innerHTML = `
            <div style="display:flex; align-items:flex-start; gap:12px;">
                <span style="font-size:1.4rem; font-weight:bold; flex-shrink:0;">${icon}</span>
                <div style="flex:1; line-height:1.4;">
                    <div style="font-weight:600; margin-bottom:4px; font-size:1rem;">${type.toUpperCase()}</div>
                    <div style="color:#374151; font-size:.95rem;">${message}</div>
                </div>
                <button style="
                    background:none; border:none; font-size:1.4rem; 
                    color:#9ca3af; cursor:pointer; padding:0 4px; margin-left:8px;
                    border-radius:4px; width:28px; height:28px; display:flex;
                    align-items:center; justify-content:center;
                " onclick="notificationManager.remove(this.parentElement.parentElement)">×</button>
            </div>
        `;
        
        // Animate in
        container.appendChild(notification);
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        });
        
        // Auto dismiss
        setTimeout(() => this.remove(notification), duration);
        
        // Manual dismiss
        notification.addEventListener('click', (e) => {
            if (!e.target.matches('button')) this.remove(notification);
        });
    }
    
    remove(notification) {
        notification.style.transform = 'translateX(400px)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) notification.parentNode.removeChild(notification);
        }, 400);
    }
    
    clearAll() {
        document.querySelectorAll('#notifications-container .notification').forEach(n => this.remove(n));
    }
}

// 🎵 Global instance
const notificationManager = new NotificationManager();

// 🌍 Global showNotification function (for your existing code)
function showNotification(message, type = 'info', duration = 5000) {
    notificationManager.showNotification(message, type, duration);
}

// 📱 Mobile optimized
if (window.innerWidth < 768) {
    document.getElementById('notifications-container').style.cssText += `
        top: 120px !important; right: 10px !important; left: 10px !important; 
        right: auto !important; max-width: none !important;
    `;
}