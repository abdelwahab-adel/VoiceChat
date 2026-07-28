/**
 * VoiceChat — WebRTC Voice Client
 * 
 * Handles:
 *  - getUserMedia (microphone capture)
 *  - Peer connections (mesh network for mic seats)
 *  - Audio processing (echo cancellation, noise suppression, AGC)
 *  - Signalling via WebSocket
 *  - Voice activity detection
 *  - Reconnection logic
 */
(function() {
    'use strict';

    class VoiceClient {
        constructor(options = {}) {
            this.roomId = options.roomId;
            this.userId = options.userId;
            this.wsUrl  = options.wsUrl || (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.hostname + ':8080';
            this.ws = null;
            this.localStream = null;
            this.peers = new Map();     // userId -> { pc, audioEl, stream }
            this.muted = false;
            this.deafened = false;
            this.speaking = false;
            this.audioContext = null;
            this.analyser = null;
            this.vadInterval = null;
            this.reconnectTimer = null;
            this.isMicSeat = false;
            this.seatIndex = null;
            this.participants = new Map();
            this.onParticipantUpdate = options.onParticipantUpdate || (() => {});
            this.onMessage = options.onMessage || (() => {});
            this.onSignal = options.onSignal || (() => {});
            this.onSpeaking = options.onSpeaking || (() => {});
            this.onConnect = options.onConnect || (() => {});
            this.onDisconnect = options.onDisconnect || (() => {});

            this.rtcConfig = {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                ],
            };
        }

        // ========== WebSocket ==========
        connect(token) {
            return new Promise((resolve, reject) => {
                try {
                    const url = this.wsUrl + '?room=' + this.roomId + '&token=' + encodeURIComponent(token || '');
                    this.ws = new WebSocket(url);
                    this.ws.onopen = () => {
                        console.log('[WS] Connected');
                        this.send({ type: 'hello', user_id: this.userId, room_id: this.roomId });
                        this.onConnect();
                        resolve();
                    };
                    this.ws.onmessage = (e) => this.handleMessage(JSON.parse(e.data));
                    this.ws.onerror = (e) => console.error('[WS] Error', e);
                    this.ws.onclose = () => {
                        console.log('[WS] Disconnected');
                        this.onDisconnect();
                        this.scheduleReconnect(token);
                    };
                } catch (e) {
                    reject(e);
                }
            });
        }

        scheduleReconnect(token) {
            if (this.reconnectTimer) return;
            this.reconnectTimer = setTimeout(() => {
                this.reconnectTimer = null;
                this.connect(token).catch(e => console.warn('Reconnect failed', e));
            }, 3000);
        }

        send(data) {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify(data));
            }
        }

        handleMessage(msg) {
            switch (msg.type) {
                case 'participants':
                    this.handleParticipants(msg.data);
                    break;
                case 'participant_joined':
                    this.handleParticipantJoined(msg.data);
                    break;
                case 'participant_left':
                    this.handleParticipantLeft(msg.data);
                    break;
                case 'offer':
                    this.handleOffer(msg.from, msg.payload);
                    break;
                case 'answer':
                    this.handleAnswer(msg.from, msg.payload);
                    break;
                case 'ice':
                    this.handleIce(msg.from, msg.payload);
                    break;
                case 'seat_taken':
                case 'seat_freed':
                case 'seat_locked':
                case 'seat_unlocked':
                case 'user_joined':
                case 'user_left':
                case 'user_muted':
                case 'user_unmuted':
                case 'hand_raised':
                case 'hand_lowered':
                case 'room_locked':
                    this.onSignal(msg);
                    break;
                case 'chat_message':
                    this.onMessage(msg);
                    break;
            }
        }

        // ========== Local Audio ==========
        async startMic(constraints = {}) {
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        channelCount: 1,
                        sampleRate: 48000,
                        ...constraints,
                    },
                    video: false,
                });
                this.setupVAD();
                this.muted = false;
                return this.localStream;
            } catch (e) {
                console.error('Microphone access failed:', e);
                throw e;
            }
        }

        stopMic() {
            if (this.localStream) {
                this.localStream.getTracks().forEach(t => t.stop());
                this.localStream = null;
            }
            if (this.vadInterval) {
                clearInterval(this.vadInterval);
                this.vadInterval = null;
            }
        }

        toggleMute() {
            if (!this.localStream) return false;
            const track = this.localStream.getAudioTracks()[0];
            if (track) {
                this.muted = !track.enabled;
                track.enabled = !track.enabled;
                this.send({ type: this.muted ? 'mute' : 'unmute' });
                return this.muted;
            }
            return false;
        }

        // ========== VAD (Voice Activity Detection) ==========
        setupVAD() {
            if (!this.localStream) return;
            try {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.audioContext.createMediaStreamSource(this.localStream);
                this.analyser = this.audioContext.createAnalyser();
                this.analyser.fftSize = 512;
                source.connect(this.analyser);
                const data = new Uint8Array(this.analyser.frequencyBinCount);
                let lastSpeaking = false;
                this.vadInterval = setInterval(() => {
                    this.analyser.getByteFrequencyData(data);
                    let sum = 0;
                    for (let i = 0; i < data.length; i++) sum += data[i];
                    const avg = sum / data.length;
                    const isSpeaking = avg > 15 && !this.muted;
                    if (isSpeaking !== lastSpeaking) {
                        lastSpeaking = isSpeaking;
                        this.onSpeaking(isSpeaking);
                        this.send({ type: isSpeaking ? 'speaking_start' : 'speaking_stop' });
                    }
                }, 200);
            } catch (e) {
                console.warn('VAD setup failed', e);
            }
        }

        // ========== Peer Connections ==========
        async createPeer(remoteUserId, initiator = false) {
            if (this.peers.has(remoteUserId)) return this.peers.get(remoteUserId);

            const pc = new RTCPeerConnection(this.rtcConfig);
            const peer = { pc, audioEl: null, initiator };
            this.peers.set(remoteUserId, peer);

            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    pc.addTrack(track, this.localStream);
                });
            }

            pc.ontrack = (e) => {
                const stream = e.streams[0];
                let audio = peer.audioEl;
                if (!audio) {
                    audio = new Audio();
                    audio.autoplay = true;
                    audio.id = 'remote-audio-' + remoteUserId;
                    document.body.appendChild(audio);
                    peer.audioEl = audio;
                }
                audio.srcObject = stream;
                audio.muted = this.deafened;
            };

            pc.onicecandidate = (e) => {
                if (e.candidate) {
                    this.send({ type: 'ice', to: remoteUserId, payload: e.candidate });
                }
            };

            pc.onconnectionstatechange = () => {
                if (['failed','closed','disconnected'].includes(pc.connectionState)) {
                    this.closePeer(remoteUserId);
                }
            };

            if (initiator) {
                try {
                    const offer = await pc.createOffer({ offerToReceiveAudio: true });
                    await pc.setLocalDescription(offer);
                    this.send({ type: 'offer', to: remoteUserId, payload: offer });
                } catch (e) {
                    console.error('createOffer failed', e);
                }
            }
            return peer;
        }

        async handleOffer(from, payload) {
            const peer = await this.createPeer(from, false);
            try {
                await peer.pc.setRemoteDescription(new RTCSessionDescription(payload));
                const answer = await peer.pc.createAnswer();
                await peer.pc.setLocalDescription(answer);
                this.send({ type: 'answer', to: from, payload: answer });
            } catch (e) {
                console.error('handleOffer failed', e);
            }
        }

        async handleAnswer(from, payload) {
            const peer = this.peers.get(from);
            if (!peer) return;
            try {
                await peer.pc.setRemoteDescription(new RTCSessionDescription(payload));
            } catch (e) {
                console.error('handleAnswer failed', e);
            }
        }

        async handleIce(from, payload) {
            const peer = this.peers.get(from);
            if (!peer) return;
            try {
                await peer.pc.addIceCandidate(new RTCIceCandidate(payload));
            } catch (e) {
                console.error('handleIce failed', e);
            }
        }

        closePeer(userId) {
            const peer = this.peers.get(userId);
            if (!peer) return;
            try { peer.pc.close(); } catch (e) {}
            if (peer.audioEl) {
                peer.audioEl.pause();
                peer.audioEl.srcObject = null;
                peer.audioEl.remove();
            }
            this.peers.delete(userId);
        }

        // ========== Participants ==========
        handleParticipants(list) {
            this.participants.clear();
            list.forEach(p => this.participants.set(p.user_id, p));
            this.onParticipantUpdate(Array.from(this.participants.values()));
        }

        handleParticipantJoined(participant) {
            this.participants.set(participant.user_id, participant);
            this.onParticipantUpdate(Array.from(this.participants.values()));
            // If they're a speaker and we are too, initiate a peer connection
            if ((participant.role === 'speaker' || participant.role === 'owner' || participant.role === 'admin' || participant.role === 'moderator')
                && (this.isMicSeat)) {
                this.createPeer(participant.user_id, true);
            }
        }

        handleParticipantLeft(data) {
            this.closePeer(data.user_id);
            this.participants.delete(data.user_id);
            this.onParticipantUpdate(Array.from(this.participants.values()));
        }

        // ========== Deafen ==========
        setDeafened(deafened) {
            this.deafened = deafened;
            this.peers.forEach(peer => {
                if (peer.audioEl) peer.audioEl.muted = deafened;
            });
        }

        // ========== Cleanup ==========
        disconnect() {
            if (this.reconnectTimer) { clearTimeout(this.reconnectTimer); this.reconnectTimer = null; }
            this.peers.forEach((_, id) => this.closePeer(id));
            this.stopMic();
            if (this.ws) {
                try { this.ws.close(); } catch (e) {}
                this.ws = null;
            }
            if (this.audioContext) {
                try { this.audioContext.close(); } catch (e) {}
                this.audioContext = null;
            }
        }
    }

    window.VoiceClient = VoiceClient;
})();
