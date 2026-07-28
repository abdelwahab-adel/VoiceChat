@extends('layouts.app')

@section('head')
<style>
.room-stage { background:linear-gradient(180deg, #0a0e1a 0%, #1a1530 100%); min-height: calc(100vh - 80px); padding:1rem 0 6rem; position:relative; }
.room-header { display:flex; align-items:center; gap:1rem; padding:1rem; background:rgba(20,25,35,0.85); backdrop-filter:blur(20px); border-radius:20px; border:1px solid rgba(255,255,255,0.06); margin-bottom:1.5rem; }
.room-cover-thumb { width:80px; height:80px; border-radius:16px; background-size:cover; background-position:center; flex-shrink:0; }
.room-title-lg { font-size:1.5rem; font-weight:700; margin:0; }
.live-dot { display:inline-block; width:8px; height:8px; border-radius:50%; background:#1ed760; margin-right:6px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity:1; transform:scale(1); } 50% { opacity:0.6; transform:scale(1.3); } }
.mics-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap:1rem; padding:1rem; }
.mic-seat { position:relative; aspect-ratio: 1; background:rgba(20,25,35,0.7); border:2px dashed rgba(255,255,255,0.1); border-radius:24px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; padding:0.75rem; }
.mic-seat:hover { border-color:rgba(94,62,255,0.5); transform:translateY(-2px); }
.mic-seat.occupied { border-style:solid; border-color:rgba(94,62,255,0.5); background:rgba(94,62,255,0.1); }
.mic-seat.locked { opacity:0.4; cursor:not-allowed; }
.mic-seat.speaking { border-color:#1ed760; box-shadow:0 0 30px rgba(30,215,96,0.4); }
.mic-avatar { width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg,#5e3eff,#ff5e8a); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:1.25rem; position:relative; }
.mic-avatar img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
.mic-name { margin-top:0.5rem; font-size:0.85rem; font-weight:600; text-align:center; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mic-mute { position:absolute; bottom:8px; right:8px; width:24px; height:24px; border-radius:50%; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; color:#ff5e5e; font-size:0.75rem; }
.room-side { background:rgba(20,25,35,0.7); border-radius:20px; padding:1rem; }
.chat-area { height:380px; overflow-y:auto; padding:0.5rem; }
.chat-msg { display:flex; gap:0.5rem; margin-bottom:0.75rem; font-size:0.9rem; }
.chat-msg .bubble { background:rgba(255,255,255,0.06); border-radius:12px; padding:0.5rem 0.75rem; max-width:80%; }
.chat-msg.system { color:#a4abbd; font-style:italic; justify-content:center; }
.chat-msg.gift { background:rgba(255,94,138,0.1); }
.participants-list { max-height:300px; overflow-y:auto; }
.participant-item { display:flex; align-items:center; gap:0.5rem; padding:0.5rem; border-radius:10px; margin-bottom:0.25rem; }
.participant-item:hover { background:rgba(255,255,255,0.04); }
.mini-avatar { width:32px; height:32px; border-radius:50%; object-fit:cover; }
.room-controls { position:fixed; bottom:0; left:0; right:0; background:rgba(10,14,26,0.95); backdrop-filter:blur(20px); border-top:1px solid rgba(255,255,255,0.08); padding:0.75rem 1rem; z-index:100; }
.control-btn { width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.06); border:none; color:#fff; font-size:1.25rem; transition:all 0.2s; }
.control-btn:hover { background:rgba(255,255,255,0.12); }
.control-btn.danger { background:rgba(255,80,80,0.2); color:#ff5e5e; }
.control-btn.live { background:rgba(30,215,96,0.2); color:#1ed760; }
.gift-popup { position:fixed; top:20%; right:20px; background:rgba(20,25,35,0.95); padding:1rem; border-radius:16px; border:1px solid rgba(255,94,138,0.5); z-index:200; animation: gift-in 0.5s ease-out; }
@keyframes gift-in { from { transform:translateX(100px); opacity:0; } to { transform:translateX(0); opacity:1; } }
</style>
@endsection

@section('content')
<div class="room-stage">
    <div class="container">
        <!-- Room Header -->
        <div class="room-header">
            <div class="room-cover-thumb" style="background-image:url('{{ $room['cover'] ? url('public/' . $room['cover']) : asset('assets/images/room-default.svg') }}')"></div>
            <div class="flex-grow-1">
                <h1 class="room-title-lg">{{ $room['name'] }}
                    @if($room['is_featured'])<i class="bi bi-star-fill text-warning"></i>@endif
                </h1>
                <div class="d-flex align-items-center gap-3 text-muted small">
                    <span><span class="live-dot"></span>LIVE</span>
                    <span><i class="bi bi-people"></i> {{ $room['current_listeners'] ?? 0 }} listeners</span>
                    <span><i class="bi bi-mic"></i> {{ $room['mic_count'] ?? 0 }}/{{ $room['max_seats'] }} mics</span>
                    <span class="badge bg-secondary">{{ $room['category'] }}</span>
                    @if($room['type'] === 'password')<span><i class="bi bi-lock"></i> Private</span>@endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-ghost btn-sm" id="btnToggleMute" title="Toggle mic"><i class="bi bi-mic-mute"></i></button>
                <button class="btn btn-ghost btn-sm" id="btnToggleDeafen" title="Toggle audio"><i class="bi bi-volume-mute"></i></button>
                <button class="btn btn-primary-gradient btn-sm" id="btnSendGift" data-room-id="{{ $room['id'] }}"><i class="bi bi-gift me-1"></i> Gift</button>
            </div>
        </div>

        <div class="row g-3">
            <!-- Mics Stage -->
            <div class="col-lg-8">
                <div class="glass-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0"><i class="bi bi-mic-fill me-2 text-primary"></i> Stage</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $room['mic_count'] ?? 0 }} on stage</span>
                    </div>
                    <div class="mics-grid" id="micsGrid">
                        @for($i = 0; $i < (int) $room['max_seats']; $i++)
                            @php
                                $seat = collect($participants)->firstWhere('seat_index', $i);
                            @endphp
                            <div class="mic-seat {{ $seat ? 'occupied' : '' }}" data-seat="{{ $i }}">
                                @if($seat)
                                    <div class="mic-avatar">
                                        @if(!empty($seat['avatar']))
                                            <img src="{{ url('public/' . $seat['avatar']) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($seat['display_name'] ?? $seat['username'] ?? 'U', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="mic-name">{{ $seat['display_name'] ?? $seat['username'] }}</div>
                                    @if($seat['is_muted'] ?? false)
                                        <div class="mic-mute"><i class="bi bi-mic-mute-fill"></i></div>
                                    @endif
                                @else
                                    <i class="bi bi-mic-mute fs-1 text-muted opacity-50"></i>
                                    <div class="mic-name text-muted">Empty</div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Chat -->
                <div class="glass-card p-3 mt-3">
                    <h6 class="mb-2"><i class="bi bi-chat-quote me-2"></i> Chat</h6>
                    <div class="chat-area" id="chatArea">
                        @foreach($messages as $m)
                            <div class="chat-msg {{ $m['type'] === 'system' ? 'system' : '' }} {{ $m['type'] === 'gift' ? 'gift' : '' }}">
                                <img src="{{ $m['avatar'] ? url('public/' . $m['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                                <div>
                                    <strong class="small text-primary">{{ $m['display_name'] ?? $m['username'] }}</strong>
                                    <div class="bubble">{{ $m['content'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($user)
                    <form id="chatForm" class="d-flex gap-2 mt-2">
                        <input type="text" class="form-control" id="chatInput" placeholder="Say something..." maxlength="500">
                        <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-send"></i></button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Sidebar: Participants + Raise Hand -->
            <div class="col-lg-4">
                <div class="room-side">
                    <h6 class="mb-3"><i class="bi bi-people me-2"></i> In the room ({{ count($participants) }})</h6>
                    <div class="participants-list" id="participantsList">
                        @foreach($participants as $p)
                            <div class="participant-item" data-user-id="{{ $p['user_id'] }}">
                                <img src="{{ $p['avatar'] ? url('public/' . $p['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                                <div class="flex-grow-1">
                                    <div class="small fw-semibold">
                                        {{ $p['display_name'] ?? $p['username'] }}
                                        @if($p['is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info"></i>@endif
                                    </div>
                                    <small class="text-muted">{{ ucfirst($p['role'] ?? 'listener') }}</small>
                                </div>
                                @if($p['is_hand_raised'] ?? false)
                                    <span class="badge bg-warning">✋</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($user)
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-warning" id="btnRaiseHand"><i class="bi bi-hand-index me-1"></i> Raise Hand</button>
                        <button class="btn btn-outline-danger" id="btnLeaveRoom"><i class="bi bi-box-arrow-left me-1"></i> Leave</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Controls -->
@if($user)
<div class="room-controls">
    <div class="container d-flex align-items-center justify-content-center gap-3">
        <button class="control-btn" id="ctrlMute" title="Mute"><i class="bi bi-mic-fill"></i></button>
        <button class="control-btn" id="ctrlDeafen" title="Deafen"><i class="bi bi-volume-up-fill"></i></button>
        <button class="control-btn" id="ctrlHand" title="Raise hand"><i class="bi bi-hand-index"></i></button>
        <button class="control-btn danger" id="ctrlLeave" title="Leave"><i class="bi bi-telephone-x"></i></button>
    </div>
</div>
@endif

<!-- Gift Modal -->
<div class="modal fade" id="giftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title">Send a Gift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="giftGrid">Loading gifts…</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.ROOM_ID = {{ $room['id'] }};
window.ROOM_MAX_SEATS = {{ $room['max_seats'] }};
window.ROOM_OWNER_ID = {{ $room['owner_id'] }};
window.USER_ID = {{ $user['id'] ?? 0 }};
window.CSRF = '{{ $csrf }}';
</script>
<script src="{{ asset('assets/js/voice-client.js') }}"></script>
<script src="{{ asset('assets/js/room.js') }}"></script>
@endsection
