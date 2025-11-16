import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

// Register Alpine plugins
Alpine.plugin(persist);
Alpine.plugin(focus);
Alpine.plugin(collapse);

// ========================================
// Global Alpine Stores
// ========================================

// Auth Store - User authentication state
Alpine.store('auth', {
    user: Alpine.$persist(null).as('auth_user'),
    token: Alpine.$persist(null).as('auth_token'),
    refreshToken: Alpine.$persist(null).as('auth_refresh_token'),

    isAuthenticated() {
        return this.token !== null;
    },

    hasRole(role) {
        return this.user?.role === role;
    },

    hasPermission(permission) {
        return this.user?.permissions?.includes(permission) || false;
    },

    setAuth(data) {
        this.token = data.access_token;
        this.refreshToken = data.refresh_token;
        this.user = data.user;
    },

    clearAuth() {
        this.user = null;
        this.token = null;
        this.refreshToken = null;
        localStorage.removeItem('auth_user');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_refresh_token');
    },

    logout() {
        this.clearAuth();
        window.location.href = '/login';
    },
});

// Theme Store - Theme and dark mode management
Alpine.store('theme', {
    current: Alpine.$persist('default').as('theme_current'),
    mode: Alpine.$persist('light').as('theme_mode'),

    themes: [
        { id: 'default', name: 'Default', preview: '#3b82f6' },
        { id: 'blue', name: 'Blue', preview: '#2563eb' },
        { id: 'green', name: 'Green', preview: '#10b981' },
        { id: 'purple', name: 'Purple', preview: '#a855f7' },
        { id: 'ocean', name: 'Ocean', preview: '#06b6d4' },
    ],

    setTheme(theme) {
        this.current = theme;
        this.applyTheme();
    },

    toggleMode() {
        this.mode = this.mode === 'light' ? 'dark' : 'light';
        this.applyMode();
    },

    setMode(mode) {
        this.mode = mode;
        this.applyMode();
    },

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.current);
    },

    applyMode() {
        document.documentElement.setAttribute('data-mode', this.mode);
    },

    init() {
        this.applyTheme();
        this.applyMode();
    },
});

// Toast Notification Store - Global toast notifications
Alpine.store('toast', {
    notifications: [],

    add(notification) {
        const id = Date.now();
        const toast = {
            id,
            type: notification.type || 'info',
            message: notification.message || '',
            title: notification.title || null,
            duration: notification.duration || 5000,
        };

        this.notifications.push(toast);

        if (toast.duration > 0) {
            setTimeout(() => this.remove(id), toast.duration);
        }

        return id;
    },

    remove(id) {
        this.notifications = this.notifications.filter((n) => n.id !== id);
    },

    success(message, title = null, duration = 5000) {
        return this.add({ type: 'success', message, title, duration });
    },

    error(message, title = null, duration = 7000) {
        return this.add({ type: 'error', message, title, duration });
    },

    warning(message, title = null, duration = 6000) {
        return this.add({ type: 'warning', message, title, duration });
    },

    info(message, title = null, duration = 5000) {
        return this.add({ type: 'info', message, title, duration });
    },

    clear() {
        this.notifications = [];
    },
});

// Sidebar Store - Sidebar state management
Alpine.store('sidebar', {
    isOpen: Alpine.$persist(false).as('sidebar_open'),
    isMobile: window.innerWidth < 1024,

    toggle() {
        this.isOpen = !this.isOpen;
    },

    open() {
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
    },

    init() {
        // Close sidebar on mobile by default
        if (this.isMobile) {
            this.isOpen = false;
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth < 1024;
            if (!this.isMobile) {
                this.isOpen = false;
            }
        });
    },
});

// Loading Store - Global loading state
Alpine.store('loading', {
    active: false,
    message: 'Loading...',

    start(message = 'Loading...') {
        this.active = true;
        this.message = message;
    },

    stop() {
        this.active = false;
        this.message = 'Loading...';
    },
});

// ========================================
// Global Alpine Magic Properties
// ========================================

// $api - Helper for making API requests
Alpine.magic('api', () => {
    return {
        async request(method, url, data = null, config = {}) {
            const token = Alpine.store('auth').token;

            const defaultConfig = {
                method,
                url,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(token && { Authorization: `Bearer ${token}` }),
                },
                ...config,
            };

            if (data) {
                defaultConfig.data = data;
            }

            try {
                const response = await axios(defaultConfig);
                return response.data;
            } catch (error) {
                // Handle 401 Unauthorized
                if (error.response?.status === 401) {
                    Alpine.store('auth').logout();
                }
                throw error;
            }
        },

        get(url, config = {}) {
            return this.request('GET', url, null, config);
        },

        post(url, data, config = {}) {
            return this.request('POST', url, data, config);
        },

        put(url, data, config = {}) {
            return this.request('PUT', url, data, config);
        },

        patch(url, data, config = {}) {
            return this.request('PATCH', url, data, config);
        },

        delete(url, config = {}) {
            return this.request('DELETE', url, null, config);
        },
    };
});

// ========================================
// Initialize Alpine
// ========================================

// Make Alpine available globally
window.Alpine = Alpine;

// Initialize theme on app start
document.addEventListener('alpine:init', () => {
    Alpine.store('theme').init();
    Alpine.store('sidebar').init();
});

// Start Alpine
Alpine.start();

// Log Alpine ready
console.log('🎨 Alpine.js initialized with stores: auth, theme, toast, sidebar, loading');
