/**
 * Application Global JavaScript
 */

const App = {
    init() {
        this.initTheme();
        this.initSidebar();
        this.initTooltips();
    },

    // --- Theme Management (Dark/Light Mode) ---
    initTheme() {
        const themeBtn = document.getElementById('themeToggle');
        const body = document.body;
        
        // Check saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            body.classList.add('dark-mode');
            if (themeBtn) themeBtn.innerHTML = '<i class="ph ph-sun"></i>';
        } else {
            body.classList.remove('dark-mode');
            if (themeBtn) themeBtn.innerHTML = '<i class="ph ph-moon"></i>';
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                themeBtn.innerHTML = isDark ? '<i class="ph ph-sun"></i>' : '<i class="ph ph-moon"></i>';
                
                // Trigger event for charts to update colors
                window.dispatchEvent(new Event('themeChanged'));
            });
        }
    },

    // --- Sidebar Toggle ---
    initSidebar() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                // Adjust main content margin if needed
            });
            
            // On mobile, hide sidebar by default
            if (window.innerWidth < 768) {
                sidebar.classList.add('collapsed');
            }
        }
    },

    // --- Tooltips ---
    initTooltips() {
        // Simple implementation if needed
    },

    // --- Toast Notification System ---
    showToast(title, message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const iconMap = {
            success: 'check-circle',
            error: 'x-circle',
            warning: 'warning-circle',
            info: 'info'
        };

        const iconName = iconMap[type] || 'info';

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="ph-fill ph-${iconName} toast-icon"></i>
            <div class="toast-content">
                <h4>${title}</h4>
                <p>${message}</p>
            </div>
        `;

        container.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease-out forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // --- Utility: Format Currency ---
    formatCurrency(amount) {
        // Assuming $ is default, can be dynamically set
        return '$' + parseFloat(amount).toFixed(2);
    },

    // --- Admin Notifications Polling & History ---
    initNotifications() {
        if (window.currentUserRole !== 'admin') return;

        let lastNotificationId = 0;
        const notifBadge = document.getElementById('notifBadge');
        const notifHeaderCount = document.getElementById('notifHeaderCount');
        const notifBtn = document.getElementById('notificationBtn');
        const notifDropdown = document.getElementById('notificationDropdown');
        const notifList = document.getElementById('notificationList');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        const clearAllNotifsBtn = document.getElementById('clearAllNotifsBtn');
        
        let unreadCount = 0;

        const updateBadge = (count) => {
            unreadCount = parseInt(count);
            if (notifHeaderCount) {
                notifHeaderCount.textContent = unreadCount;
            }
            if (unreadCount > 0) {
                notifBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                notifBadge.style.display = 'block';
            } else {
                notifBadge.style.display = 'none';
            }
        };

        const pollNotifications = async () => {
            try {
                const response = await fetch(`../api/notifications.php?action=poll&last_id=${lastNotificationId}`);
                if (!response.ok) return;
                
                const data = await response.json();
                if (data.success) {
                    updateBadge(data.unread_count);

                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            this.showToast(notif.title, notif.message, notif.type || 'info');
                            lastNotificationId = Math.max(lastNotificationId, parseInt(notif.id));
                        });

                        // Add a pulse animation class to draw attention
                        if (notifBadge) {
                            notifBadge.classList.add('pulse');
                            setTimeout(() => notifBadge.classList.remove('pulse'), 3000);
                        }
                        
                        // If dropdown is open, refresh history
                        if (notifDropdown.classList.contains('active')) {
                            loadHistory();
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to poll notifications', error);
            }
        };

        const timeAgo = (dateStr) => {
            const date = new Date(dateStr);
            const seconds = Math.floor((new Date() - date) / 1000);
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            return Math.floor(seconds) + " seconds ago";
        };

        const loadHistory = async () => {
            notifList.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--text-muted);"><i class="ph ph-spinner ph-spin" style="font-size: 1.5rem;"></i></div>';
            try {
                const response = await fetch('../api/notifications.php?action=history');
                const data = await response.json();
                
                if (data.success) {
                    if (!data.notifications || data.notifications.length === 0) {
                        notifList.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No notifications yet.</div>';
                        return;
                    }
                    
                    notifList.innerHTML = data.notifications.map(n => `
                        <div class="notification-item ${n.is_read == 0 ? 'unread' : ''}" data-id="${n.id}" data-read="${n.is_read}">
                            <div class="notif-icon">
                                <i class="ph-fill ${n.type === 'success' ? 'ph-check-circle text-success' : 'ph-info text-info'}"></i>
                            </div>
                            <div class="notif-content">
                                <h5>${this.escapeHtml(n.title)}</h5>
                                <p>${this.escapeHtml(n.message)}</p>
                                <span class="notif-time">${timeAgo(n.created_at)}</span>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (err) {
                notifList.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--danger-color);">Failed to load history.</div>';
            }
        };

        const markAsRead = async (id) => {
            try {
                const response = await fetch('../api/notifications.php?action=mark_read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        id: id, 
                        csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '' 
                    })
                });
                const data = await response.json();
                if (data.success) {
                    if (id === 'all') {
                        updateBadge(0);
                        document.querySelectorAll('.notification-item.unread').forEach(el => {
                            el.classList.remove('unread');
                            el.dataset.read = "1";
                        });
                    } else {
                        updateBadge(Math.max(0, unreadCount - 1));
                        const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                        if (item) {
                            item.classList.remove('unread');
                            item.dataset.read = "1";
                        }
                    }
                }
            } catch (err) {
                console.error("Failed to mark read", err);
            }
        };

        const clearAllNotifications = async () => {
            if (!confirm('Are you sure you want to clear all notifications?')) return;
            try {
                const response = await fetch('../api/notifications.php?action=clear_all', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || '' 
                    })
                });
                const data = await response.json();
                if (data.success) {
                    updateBadge(0);
                    notifList.innerHTML = '<div style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No notifications yet.</div>';
                }
            } catch (err) {
                console.error("Failed to clear notifications", err);
            }
        };

        // Event Listeners
        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notifDropdown.classList.toggle('active');
                if (notifDropdown.classList.contains('active')) {
                    loadHistory();
                }
            });

            document.addEventListener('click', (e) => {
                if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove('active');
                }
            });

            notifList.addEventListener('click', (e) => {
                const item = e.target.closest('.notification-item');
                if (item && item.dataset.read === "0") {
                    markAsRead(item.dataset.id);
                }
            });

            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', () => markAsRead('all'));
            }
            if (clearAllNotifsBtn) {
                clearAllNotifsBtn.addEventListener('click', clearAllNotifications);
            }
        }

        // Helper to escape HTML safely
        this.escapeHtml = (unsafe) => {
            return (unsafe || '').toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        };

        // Start polling
        setInterval(pollNotifications, 10000);
        pollNotifications(); // initial load
    }
};

document.addEventListener('DOMContentLoaded', () => {
    App.init();
    App.initNotifications();
});
