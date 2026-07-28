@extends('layouts.app')
@section('head')
<style>
.chat-page { display:flex; flex-direction:column; height: calc(100vh - 80px); }
.chat-header { background:rgba(20,25,35,0.85); backdrop-filter:blur(20px); padding:0.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; gap:0.75rem; }
.chat-body { flex:1; overflow-y:auto; padding:1rem; }
.msg-bubble { max-width:65%; padding:0.6rem 0.9rem; border-radius:18px; margin-bottom:0.5rem; word-wrap:break-word; }
.msg-mine { background:linear-gradient(135deg,#5e3eff,#7e5eff); color:#fff; margin-left:auto; border-bottom-right-radius:6px; }
.msg-theirs { background:rgba(255,255,255,0.08); margin-right:auto; border-bottom-left-radius:6px; }
.chat-footer { background:rgba(20,25,35,0.85); backdrop-filter:blur(20px); padding:0.75rem 1rem; border-top:1px solid rgba(255,255,255,0.06); }
.msg-typing { font-size:0.8rem; color:#a4abbd; font-style:italic; padding:0.25rem 0.5rem; }
</style>
@endsection

@section('content')
<div class="chat-page">
    <div class="chat-header">
        <a href="{{ url('messages') }}" class="btn btn-sm btn-ghost d-md-none"><i class="bi bi-arrow-left"></i></a>
        <img src="{{ $other['avatar'] ? url('public/' . $other['avatar']) : asset('assets/images/default-avatar.svg') }}" class="msg-avatar-mini">
        <div class="flex-grow-1">
            <a href="{{ url('u/' . $other['username']) }}" class="fw-semibold text-decoration-none">{{ $other['display_name'] ?? $other['username'] }}
            @if($other['is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif
            </a>
            <div class="small"><span class="online-dot {{ $other['online_status'] === 'online' ? 'online' : '' }}"></span> {{ ucfirst($other['online_status'] ?? 'offline') }}</div>
        </div>
    </div>

    <div class="chat-body" id="chatBody">
        @foreach($messages as $m)
            @php $mine = ((int)$m['sender_id'] === (int)$this->user()['id']); @endphp
            <div class="d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="msg-bubble {{ $mine ? 'msg-mine' : 'msg-theirs' }}">
                    @if($m['type'] === 'image' && $m['media_url'])
                        <img src="{{ $m['media_url'] }}" class="img-fluid rounded">
                    @elseif($m['type'] === 'voice' && $m['media_url'])
                        <audio controls src="{{ $m['media_url'] }}"></audio>
                    @else
                        {{ $m['content'] }}
                    @endif
                    <div class="small {{ $mine ? 'text-white-50' : 'text-muted' }} mt-1">{{ time_ago($m['created_at']) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="msg-typing" id="typingIndicator" style="display:none"><i class="bi bi-three-dots"></i> typing…</div>

    <form class="chat-footer d-flex gap-2" id="chatForm" method="POST" action="{{ url('messages/' . $other['id'] . '/send') }}">
        @csrf
        <button type="button" class="btn btn-ghost" id="btnAttach"><i class="bi bi-paperclip"></i></button>
        <input type="text" class="form-control" name="content" id="msgInput" placeholder="Type a message..." maxlength="2000" autocomplete="off">
        <button type="submit" class="btn btn-primary-gradient"><i class="bi bi-send"></i></button>
    </form>
</div>
@endsection

@section('scripts')
<script>
window.PARTNER_ID = {{ $other['id'] }};
window.MY_ID = {{ $user['id'] }};
window.CSRF = '{{ $csrf }}';
</script>
<script src="{{ asset('assets/js/chat.js') }}"></script>
@endsection
