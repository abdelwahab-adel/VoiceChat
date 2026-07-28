/**
 * VoiceChat — Room UI Controller
 */
(function() {
    'use strict';

    const ROOM_ID = parseInt(window.ROOM_ID);
    const ROOM_OWNER = parseInt(window.ROOM_OWNER_ID);
    const USER_ID = parseInt(window.USER_ID);
    const CSRF = window.CSRF;
    const IS_OWNER = USER_ID === ROOM_OWNER;

    let voiceClient = null;
    let isMicSeat = false;
    let currentSeat = null;
    let participants = [];

    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatArea  = document.getElementById('chatArea');
    const micsGrid  = document.getElementById('micsGrid');
    const participantsList = document.getElementById('participantsList');

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function appendChatMessage(msg) {
        const div = document.createElement('div');
        div.className = 'chat-msg' + (msg.type === 'system' ? ' system' : '') + (msg.type === 'gift' ? ' gift' : '');
        div.innerHTML = `
            <img src="${msg.avatar ? (msg.avatar.startsWith('http') ? msg.avatar : '/public/' + msg.avatar) : '/assets/images/default-avatar.svg'}" class="mini-avatar">
            <div>
                <strong class="small text-primary">${escapeHtml(msg.display_name || msg.username || 'User')}</strong>
                <div class="bubble">${escapeHtml(msg.content || '')}</div>
            </div>`;
        chatArea.appendChild(div);
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    function renderMics() {
        if (!micsGrid) return;
        const seats = micsGrid.querySelectorAll('.mic-seat');
        const map = new Map();
        participants.forEach(p => {
            if (p.seat_index !== null && p.seat_index !== undefined) {
                map.set(p.seat_index, p);
            }
        });
        seats.forEach((seat, idx) => {
            const p = map.get(idx);
            seat.classList.toggle('occupied', !!p);
            if (p) {
                const name = p.display_name || p.username || 'User';
                const initial = name.charAt(0).toUpperCase();
                const avatar = p.avatar ? (p.avatar.startsWith('http') ? p.avatar : '/public/' + p.avatar) : '/assets/images/default-avatar.svg';
                seat.innerHTML = `
                    <div class="mic-avatar">
                        ${p.avatar ? `<img src="${avatar}" alt="">` : initial}
                    </div>
                    <div class="mic-name">${escapeHtml(name)}</div>
                    ${p.is_muted ? '<div class="mic-mute"><i class="bi bi-mic-mute-fill"></i></div>' : ''}
                `;
            } else {
                seat.innerHTML = `<i class="bi bi-mic-mute fs-1 text-muted opacity-50"></i><div class="mic-name text-muted">Empty</div>`;
            }
        });
    }

    function renderParticipants() {
        if (!participantsList) return;
        participantsList.innerHTML = participants.map(p => {
            const avatar = p.avatar ? (p.avatar.startsWith('http') ? p.avatar : '/public/' + p.avatar) : '/assets/images/default-avatar.svg';
            return `
                <div class="participant-item" data-user-id="${p.user_id}">
                    <img src="${avatar}" class="mini-avatar">
                    <div class="flex-grow-1">
                        <div class="small fw-semibold">${escapeHtml(p.display_name || p.username || 'User')}</div>
                        <small class="text-muted">${escapeHtml(p.role || 'listener')}</small>
                    </div>
                    ${p.is_hand_raised ? '<span class="badge bg-warning">✋</span>' : ''}
                </div>
            `;
        }).join('');
    }

    async function api(method, url, body = null) {
        const opts = {
            method, credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json' },
        };
        if (body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch('/api' + url, opts);
        return res.json();
    }

    // ---- Join room ----
    async function joinRoom() {
        const r = await api('POST', '/rooms/' + ROOM_ID + '/join');
        if (r.error) { toast(r.error, 'error'); return; }
        if (r.success) {
            isMicSeat = (r.data?.state?.max_seats > 0);
            // Initialise WebRTC
            voiceClient = new VoiceClient({
                roomId: ROOM_ID,
                userId: USER_ID,
                onParticipantUpdate: (list) => { participants = list; renderMics(); renderParticipants(); },
                onMessage: (msg) => { if (msg.message) appendChatMessage(msg.message); },
            });
            try { await voiceClient.connect(CSRF); } catch (e) { console.warn('WS failed', e); }
        }
    }

    // ---- Mic controls ----
    async function takeSeat(index) {
        try {
            await voiceClient.startMic();
            const r = await api('POST', '/rooms/' + ROOM_ID + '/seat', { seat_index: index, action: 'take' });
            if (r.error) { toast(r.error, 'error'); voiceClient.stopMic(); return; }
            currentSeat = index;
            isMicSeat = true;
            // Connect to existing speakers
            const others = participants.filter(p => p.user_id !== USER_ID && ['speaker','owner','admin','moderator'].includes(p.role));
            for (const p of others) {
                voiceClient.createPeer(p.user_id, true);
            }
        } catch (e) {
            toast('Microphone access required: ' + e.message, 'error');
        }
    }

    async function leaveSeat() {
        await voiceClient.stopMic();
        await api('POST', '/rooms/' + ROOM_ID + '/seat', { seat_index: currentSeat, action: 'leave' });
        currentSeat = null;
        isMicSeat = false;
    }

    // ---- Wire UI events ----
    document.getElementById('ctrlMute')?.addEventListener('click', () => {
        if (!voiceClient) return;
        const muted = voiceClient.toggleMute();
        const icon = document.querySelector('#ctrlMute i');
        icon.className = muted ? 'bi bi-mic-mute-fill' : 'bi bi-mic-fill';
        document.getElementById('ctrlMute').classList.toggle('danger', muted);
    });

    document.getElementById('ctrlDeafen')?.addEventListener('click', () => {
        if (!voiceClient) return;
        voiceClient.setDeafened(!voiceClient.deafened);
        const icon = document.querySelector('#ctrlDeafen i');
        icon.className = voiceClient.deafened ? 'bi bi-volume-mute-fill' : 'bi bi-volume-up-fill';
    });

    document.getElementById('ctrlHand')?.addEventListener('click', async () => {
        await api('POST', '/rooms/' + ROOM_ID + '/hand', { action: 'raise' });
        toast('✋ Hand raised', 'info');
    });

    document.getElementById('ctrlLeave')?.addEventListener('click', async () => {
        if (isMicSeat) await leaveSeat();
        await api('POST', '/rooms/' + ROOM_ID + '/leave');
        if (voiceClient) voiceClient.disconnect();
        location.href = '/rooms';
    });

    document.getElementById('btnRaiseHand')?.addEventListener('click', () => document.getElementById('ctrlHand').click());

    // Mic seat click
    if (micsGrid) {
        micsGrid.addEventListener('click', async (e) => {
            const seat = e.target.closest('.mic-seat');
            if (!seat) return;
            if (seat.classList.contains('occupied')) {
                // If owner/mod, allow kicking or muting
                if (IS_OWNER) {
                    const p = participants.find(x => x.seat_index == seat.dataset.seat);
                    if (p) {
                        if (confirm(`Mute ${p.display_name || p.username}?`)) {
                            await api('POST', '/rooms/' + ROOM_ID + '/mic', { action: 'mute', target: p.user_id });
                        } else if (confirm(`Kick ${p.display_name || p.username}?`)) {
                            await api('POST', '/rooms/' + ROOM_ID + '/mic', { action: 'kick', target: p.user_id });
                        }
                    }
                }
                return;
            }
            if (!isMicSeat) await takeSeat(parseInt(seat.dataset.seat));
        });
    }

    // Chat
    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = chatInput.value.trim();
            if (!content) return;
            chatInput.value = '';
            await api('POST', '/rooms/' + ROOM_ID + '/chat', { content, type: 'text' });
        });
    }

    // Gift modal
    const giftModal = document.getElementById('giftModal');
    const giftGrid  = document.getElementById('giftGrid');
    document.getElementById('btnSendGift')?.addEventListener('click', async () => {
        if (giftModal) new bootstrap.Modal(giftModal).show();
        if (giftGrid && !giftGrid.dataset.loaded) {
            giftGrid.innerHTML = 'Loading…';
            const r = await api('GET', '/gifts');
            const list = r.data || r;
            giftGrid.innerHTML = `
                <div class="row g-2">${(list.data || list).map(g => `
                    <div class="col-3">
                        <div class="gift-card rarity-${g.rarity}" data-id="${g.id}" data-price="${g.price_coins}">
                            <div class="gift-image"><img src="${g.image ? '/public/' + g.image : '/assets/images/gift-default.svg'}" alt=""></div>
                            <h6 class="gift-name">${escapeHtml(g.name)}</h6>
                            <div class="gift-price"><i class="bi bi-coin"></i> ${g.price_coins}</div>
                        </div>
                    </div>
                `).join('')}</div>`;
            giftGrid.dataset.loaded = '1';
            giftGrid.addEventListener('click', async e => {
                const card = e.target.closest('.gift-card');
                if (!card) return;
                const id = card.dataset.id;
                const target = prompt('Recipient user ID?');
                if (!target) return;
                const r2 = await api('POST', '/gifts/send', { gift_id: parseInt(id), receiver_id: parseInt(target), room_id: ROOM_ID });
                if (r2.success) { toast('🎁 Gift sent!', 'success'); bootstrap.Modal.getInstance(giftModal).hide(); }
                else toast(r2.message || 'Failed', 'error');
            });
        }
    });

    // Auto-join if user is logged in
    if (USER_ID > 0) joinRoom();

    // Cleanup on unload
    window.addEventListener('beforeunload', () => { if (voiceClient) voiceClient.disconnect(); });
})();
