/**
 * VoiceChat — Main App JS
 * Handles:
 *  - Bootstrap helpers
 *  - API wrappers
 *  - Toast notifications
 *  - Theme toggle
 *  - Notification polling
 *  - Common UI behaviors
 */
(function() {
    'use strict';

    const API = {
        token: document.querySelector('meta[name="csrf-token"]')?.content || '',
        base: '/api',

        async request(method, url, data = null) {
            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.token,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            };
            if (data) opts.body = JSON.stringify(data);
            const res = await fetch(this.base + url, opts);
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(json.message || json.error || res.statusText);
            }
            return json;
        },
        get(url) { return this.request('GET', url); },
        post(url, data) { return this.request('POST', url, data); },
        put(url, data) { return this.request('PUT', url, data); },
        delete(url) { return this.request('DELETE', url); },
    };

    // Expose globally
    window.API = API;
    window.CSRF = API.token;

    // ---- Toast system ----
    const toastContainer = (() => {
        let el = document.querySelector('.toast-container');
        if (!el) {
            el = document.createElement('div');
            el.className = 'toast-container position-fixed top-0 end-0 p-3';
            el.style.zIndex = '9999';
            document.body.appendChild(el);
        }
        return el;
    })();

    window.toast = function(message, type = 'info', timeout = 3500) {
        const colors = {
            success: 'bg-success text-white',
            error:   'bg-danger text-white',
            warning: 'bg-warning text-dark',
            info:    'bg-primary text-white',
        };
        const id = 't' + Date.now() + Math.random().toString(36).slice(2,6);
        const el = document.createElement('div');
        el.className = `toast align-items-center ${colors[type] || colors.info} border-0 show`;
        el.id = id;
        el.role = 'alert';
        el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        toastContainer.appendChild(el);
        setTimeout(() => el.remove(), timeout);
    };

    // ---- Confirmation helper ----
    window.confirmAction = function(message) { return confirm(message); };

    // ---- Theme toggle ----
    const savedTheme = localStorage.getItem('vc_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // ---- Notification polling ----
    let lastNotifCount = 0;
    let lastMsgCount = 0;
    async function pollNotifications() {
        if (!document.querySelector('meta[name="user-id"]')?.content) return;
        try {
            const res = await API.get('/notifications?unread=1');
            const unread = (res.data?.unread_count ?? res.unread_count ?? 0);
            const badge = document.getElementById('notifBadge');
            if (badge) badge.style.display = unread > 0 ? 'block' : 'none';
            lastNotifCount = unread;
        } catch (e) { /* silent */ }
    }
    async function pollMessages() {
        if (!document.querySelector('meta[name="user-id"]')?.content) return;
        try {
            // we re-use /api/messages count
            const res = await fetch('/api/messages', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const json = await res.json();
            const total = (json.data || []).reduce((s, c) => s + (c.unread_count || 0), 0);
            const badge = document.getElementById('msgBadge');
            if (badge) badge.style.display = total > 0 ? 'block' : 'none';
            lastMsgCount = total;
        } catch (e) { /* silent */ }
    }
    if (document.querySelector('meta[name="user-id"]')?.content) {
        pollNotifications();
        pollMessages();
        setInterval(pollNotifications, 30000);
        setInterval(pollMessages, 15000);
    }

    // ---- Auto-dismiss alerts ----
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(a => {
            try { a.classList.remove('show'); } catch (e) {}
            setTimeout(() => a.remove(), 500);
        });
    }, 5000);

    // ---- Form auto-submit loader ----
    document.addEventListener('submit', e => {
        const form = e.target;
        if (form.matches('form[data-loading]')) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Working...';
                setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 5000);
            }
        }
    });

    // ---- Auto-format file inputs ----
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                toast('File too large (max 10MB)', 'error');
                e.target.value = '';
            }
        });
    });

    // ---- Image lightbox ----
    document.addEventListener('click', e => {
        if (e.target.matches('img.lightbox')) {
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:pointer;';
            overlay.innerHTML = `<img src="${e.target.src}" style="max-width:90%;max-height:90%;">`;
            overlay.onclick = () => overlay.remove();
            document.body.appendChild(overlay);
        }
    });

    // ---- Global error handler ----
    window.addEventListener('unhandledrejection', e => {
        console.error('Unhandled promise rejection:', e.reason);
    });

    console.log('%c🎙️ VoiceChat loaded', 'background:linear-gradient(135deg,#5e3eff,#ff5e8a);color:#fff;padding:6px 12px;border-radius:6px;font-weight:700;');
})();
