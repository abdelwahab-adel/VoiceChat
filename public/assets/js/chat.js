/**
 * VoiceChat — Private chat (1:1) controller
 */
(function() {
    'use strict';
    const PARTNER_ID = parseInt(window.PARTNER_ID);
    const MY_ID = parseInt(window.MY_ID);
    const CSRF = window.CSRF;
    const form = document.getElementById('chatForm');
    const input = document.getElementById('msgInput');
    const body  = document.getElementById('chatBody');
    let typingTimer = null;

    function scrollBottom() { if (body) body.scrollTop = body.scrollHeight; }
    scrollBottom();

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function appendMessage(m, mine) {
        const div = document.createElement('div');
        div.className = 'd-flex ' + (mine ? 'justify-content-end' : 'justify-content-start');
        const time = new Date(m.created_at || Date.now()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        let content = '';
        if (m.type === 'image' && m.media_url) {
            content = `<img src="${m.media_url}" class="img-fluid rounded" style="max-width:200px">`;
        } else if (m.type === 'voice' && m.media_url) {
            content = `<audio controls src="${m.media_url}"></audio>`;
        } else {
            content = escapeHtml(m.content || '');
        }
        div.innerHTML = `<div class="msg-bubble ${mine ? 'msg-mine' : 'msg-theirs'}">${content}<div class="small ${mine ? 'text-white-50' : 'text-muted'} mt-1">${time}</div></div>`;
        body.appendChild(div);
        scrollBottom();
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const content = input.value.trim();
        if (!content) return;
        input.value = '';
        const fd = new FormData(form);
        const res = await fetch(form.action, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, credentials: 'same-origin' });
        const json = await res.json();
        if (json.success) {
            appendMessage({ content, type: 'text', created_at: new Date().toISOString() }, true);
        } else {
            toast(json.message || 'Failed to send', 'error');
        }
    });

    input.addEventListener('input', () => {
        if (typingTimer) clearTimeout(typingTimer);
        fetch('/api/messages/' + PARTNER_ID + '/typing', { method: 'POST', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' }, credentials: 'same-origin' });
        typingTimer = setTimeout(() => {}, 1500);
    });

    // Real-time updates via WebSocket
    try {
        const wsUrl = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.hostname + ':8080/?channel=msg&with=' + PARTNER_ID + '&token=' + encodeURIComponent(CSRF);
        const ws = new WebSocket(wsUrl);
        ws.onmessage = (e) => {
            try {
                const msg = JSON.parse(e.data);
                if (msg.type === 'message' && (msg.data.sender_id == PARTNER_ID || msg.data.receiver_id == PARTNER_ID)) {
                    appendMessage(msg.data, msg.data.sender_id == MY_ID);
                } else if (msg.type === 'typing' && msg.data.from == PARTNER_ID) {
                    const ti = document.getElementById('typingIndicator');
                    if (ti) ti.style.display = 'block';
                    setTimeout(() => { if (ti) ti.style.display = 'none'; }, 2000);
                }
            } catch (err) {}
        };
        window.addEventListener('beforeunload', () => ws.close());
    } catch (e) { /* WS not available, polling fallback */ }
})();
