@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:720px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0"><i class="bi bi-bell me-2"></i> Notifications</h2>
        <form method="POST" action="{{ url('notifications/read-all') }}">
            @csrf
            <button class="btn btn-sm btn-ghost">Mark all as read</button>
        </form>
    </div>

    @forelse($notifications as $n)
    <a href="{{ $n['action_url'] ?? '#' }}" class="notification-row {{ !$n['is_read'] ? 'unread' : '' }}">
        <div class="notif-icon notif-{{ $n['type'] }}">
            @switch($n['type'])
                @case('friend_request')<i class="bi bi-person-plus"></i>@break
                @case('gift_received')<i class="bi bi-gift"></i>@break
                @case('room_invite')<i class="bi bi-mic"></i>@break
                @case('message')<i class="bi bi-chat"></i>@break
                @case('follow')<i class="bi bi-person-plus"></i>@break
                @case('level_up')<i class="bi bi-arrow-up-circle"></i>@break
                @default<i class="bi bi-bell"></i>
            @endswitch
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold">{{ $n['title'] }}</div>
            <small class="text-muted">{{ $n['body'] }}</small>
            <div class="small text-muted mt-1">{{ time_ago($n['created_at']) }}</div>
        </div>
    </a>
    @empty
        <div class="glass-card p-5 text-center">
            <i class="bi bi-bell-slash display-1 text-muted opacity-25"></i>
            <p class="text-muted mt-2 mb-0">No notifications</p>
        </div>
    @endforelse
</div>
@endsection
