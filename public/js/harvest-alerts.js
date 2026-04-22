/**
 * Harvest Alert System - Real-time notifications for culture harvest
 */

class HarvestAlertManager {
    constructor() {
        this.websocket = null;
        this.userId = null;
        this.alertContainer = null;
        this.notificationSound = null;
        this.shownAlerts = new Set(); // Track shown toast notifications
        this.isVisible = false;
        this.init();
    }

    init() {
        this.createAlertContainer();
        this.loadInitialAlerts();
        this.setupMercureConnection();
        this.loadShownAlerts();
    }

    loadShownAlerts() {
        // Load from localStorage to persist across page loads
        const stored = localStorage.getItem('shownHarvestAlerts');
        if (stored) {
            try {
                const alerts = JSON.parse(stored);
                this.shownAlerts = new Set(alerts);
            } catch (e) {
                this.shownAlerts = new Set();
            }
        }
    }

    saveShownAlerts() {
        localStorage.setItem('shownHarvestAlerts', JSON.stringify([...this.shownAlerts]));
    }

    createAlertContainer() {
        if (document.getElementById('harvest-alerts-container')) {
            return;
        }

        const container = document.createElement('div');
        container.id = 'harvest-alerts-container';
        container.className = 'harvest-alerts-container hidden';
        container.innerHTML = `
            <div class="harvest-alerts-header">
                <h4>🌾 Alertes de Récolte</h4>
                <button class="close-alerts" onclick="harvestAlertManager.toggleAlerts()">×</button>
            </div>
            <div class="harvest-alerts-list" id="harvest-alerts-list">
                <p class="loading">Chargement des alertes...</p>
            </div>
            <div class="harvest-alerts-actions">
                <button class="btn-mark-all-seen" onclick="harvestAlertManager.markAllAsSeen()">
                    ✓ Tout marquer vu
                </button>
                <button class="btn-clear-seen" onclick="harvestAlertManager.clearSeen()">
                    🗑️ Effacer vus
                </button>
            </div>
        `;
        document.body.appendChild(container);
        this.alertContainer = container;
    }

    async loadInitialAlerts() {
        try {
            const response = await fetch('/api/harvest-alerts');
            const data = await response.json();
            
            if (data.success) {
                this.displayAlerts(data.alerts);
                this.updateBadge(data.unseenCount || 0);
            }
        } catch (error) {
            console.error('Failed to load harvest alerts:', error);
        }
    }

    setupMercureConnection() {
        const userId = this.getUserId();
        if (!userId) {
            console.warn('User ID not found, skipping WebSocket connection');
            return;
        }

        this.connectWebSocket(userId);
    }

    connectWebSocket(userId) {
        const wsUrl = this.getWebSocketUrl();
        console.log('Connecting to WebSocket:', wsUrl);

        try {
            this.websocket = new WebSocket(wsUrl);

            this.websocket.onopen = () => {
                console.log('WebSocket connected');
                // Register user for alerts
                this.websocket.send(JSON.stringify({
                    type: 'register',
                    userId: userId
                }));
            };

            this.websocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                console.log('WebSocket message:', data);

                if (data.type === 'registered') {
                    console.log('Successfully registered for harvest alerts');
                } else if (data.type === 'harvest_alert') {
                    this.handleNewAlert(data.alert);
                }
            };

            this.websocket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            this.websocket.onclose = () => {
                console.log('WebSocket disconnected, reconnecting in 5 seconds...');
                setTimeout(() => this.connectWebSocket(userId), 5000);
            };
        } catch (error) {
            console.error('Failed to create WebSocket:', error);
            setTimeout(() => this.connectWebSocket(userId), 5000);
        }
    }

    handleNewAlert(alertData) {
        const alertKey = `${alertData.culture.id}-${alertData.daysUntilHarvest}`;
        
        // Only show toast if not already shown in this session
        if (!this.shownAlerts.has(alertKey)) {
            this.showNotification(alertData);
            this.playNotificationSound(alertData.urgency);
            this.shownAlerts.add(alertKey);
            this.saveShownAlerts();
            
            // Ring the bell
            this.ringBell();
        }
        
        this.loadInitialAlerts(); // Refresh the list
    }

    ringBell() {
        const bellButtons = document.querySelectorAll('.harvest-alert-bell');
        bellButtons.forEach(btn => {
            btn.classList.add('ringing');
            setTimeout(() => btn.classList.remove('ringing'), 500);
        });
    }

    displayAlerts(alerts) {
        const listElement = document.getElementById('harvest-alerts-list');
        
        if (!alerts || alerts.length === 0) {
            listElement.innerHTML = '<p class="no-alerts">Aucune alerte de récolte pour le moment</p>';
            return;
        }

        // Sort: unseen first, then by urgency
        const sortedAlerts = [...alerts].sort((a, b) => {
            if (a.seen !== b.seen) return a.seen ? 1 : -1;
            const urgencyOrder = { critical: 0, high: 1, medium: 2, low: 3 };
            return urgencyOrder[a.urgency] - urgencyOrder[b.urgency];
        });

        listElement.innerHTML = sortedAlerts.map(alert => `
            <div class="harvest-alert-item urgency-${alert.urgency} ${alert.seen ? 'seen' : ''}" 
                 onclick="harvestAlertManager.markAlertSeen(${alert.id})"
                 data-alert-id="${alert.id}">
                <div class="alert-icon">${this.getUrgencyIcon(alert.urgency)}</div>
                <div class="alert-content">
                    <h5>${alert.nomCulture}</h5>
                    <p class="alert-type">${alert.typeCulture}</p>
                    <p class="alert-date">
                        ${alert.isToday ? 
                            '🚨 Récolte aujourd\'hui !' : 
                            `📅 Récolte dans ${alert.daysUntilHarvest} jour(s)`
                        }
                    </p>
                    <small>Date prévue: ${this.formatDate(alert.dateRecolte)}</small>
                </div>
            </div>
        `).join('');
    }

    async markAlertSeen(cultureId) {
        try {
            const response = await fetch(`/api/harvest-alerts/mark-seen/${cultureId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                // Update UI
                const alertElement = document.querySelector(`[data-alert-id="${cultureId}"]`);
                if (alertElement) {
                    alertElement.classList.add('seen');
                }
                this.loadInitialAlerts(); // Refresh to update badge
            }
        } catch (error) {
            console.error('Failed to mark alert as seen:', error);
        }
    }

    async markAllAsSeen() {
        try {
            const response = await fetch('/api/harvest-alerts/mark-all-seen', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                this.loadInitialAlerts();
            }
        } catch (error) {
            console.error('Failed to mark all as seen:', error);
        }
    }

    async clearSeen() {
        if (!confirm('Voulez-vous vraiment effacer toutes les alertes vues ?')) {
            return;
        }
        
        try {
            const response = await fetch('/api/harvest-alerts/clear-seen', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                this.loadInitialAlerts();
            }
        } catch (error) {
            console.error('Failed to clear seen alerts:', error);
        }
    }

    showNotification(alertData) {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'granted') {
            new Notification('🌾 Alerte de Récolte', {
                body: alertData.message,
                icon: '/images/harvest-icon.png',
                tag: `harvest-${alertData.culture.id}`,
                requireInteraction: alertData.urgency === 'critical'
            });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.showNotification(alertData);
                }
            });
        }

        // Show in-app toast notification
        this.showToast(alertData);
    }

    showToast(alertData) {
        const toast = document.createElement('div');
        toast.className = `harvest-toast urgency-${alertData.urgency}`;
        toast.innerHTML = `
            <div class="toast-icon">${this.getUrgencyIcon(alertData.urgency)}</div>
            <div class="toast-content">
                <strong>${alertData.culture.nom}</strong>
                <p>${alertData.message}</p>
            </div>
            <button onclick="this.parentElement.remove()">×</button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    playNotificationSound(urgency) {
        const audio = new Audio('/sounds/notification.mp3');
        audio.volume = urgency === 'critical' ? 1.0 : 0.5;
        audio.play().catch(e => console.log('Could not play sound:', e));
    }

    toggleAlerts() {
        if (this.alertContainer) {
            this.isVisible = !this.isVisible;
            
            // Update button active state
            const bellButtons = document.querySelectorAll('.harvest-alert-bell');
            bellButtons.forEach(btn => {
                if (this.isVisible) {
                    btn.classList.add('active');
                    this.alertContainer.classList.remove('hidden');
                } else {
                    btn.classList.remove('active');
                    this.alertContainer.classList.add('hidden');
                }
            });
        }
    }

    updateBadge(count) {
        const badges = document.querySelectorAll('.harvest-alerts-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    getUrgencyIcon(urgency) {
        const icons = {
            critical: '🚨',
            high: '⚠️',
            medium: '📅',
            low: 'ℹ️'
        };
        return icons[urgency] || 'ℹ️';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    getUserId() {
        const userElement = document.querySelector('[data-user-id]');
        return userElement ? userElement.dataset.userId : null;
    }

    getWebSocketUrl() {
        return document.querySelector('meta[name="websocket-url"]')?.content || 
               'ws://localhost:8080';
    }

    destroy() {
        if (this.websocket) {
            this.websocket.close();
        }
    }
}

// Initialize on page load
let harvestAlertManager;
document.addEventListener('DOMContentLoaded', () => {
    harvestAlertManager = new HarvestAlertManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (harvestAlertManager) {
        harvestAlertManager.destroy();
    }
});
