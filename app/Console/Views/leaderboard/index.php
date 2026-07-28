@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2><i class="bi bi-trophy me-2 text-warning"></i> Leaderboard</h2>
    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><a class="nav-link {{ $type === 'users' ? 'active' : '' }}" href="?type=users">Users</a></li>
        <li class="nav-item"><a class="nav-link {{ $type === 'agencies' ? 'active' : '' }}" href="?type=agencies">Agencies</a></li>
        <li class="nav-item"><a class="nav-link {{ $type === 'rooms' ? 'active' : '' }}" href="?type=rooms">Rooms</a></li>
    </ul>

    <div class="glass-card p-3">
        @if($type === 'users')
            @foreach($data as $i => $u)
            <div class="lb-row">
                <span class="lb-rank rank-{{ $i+1 }}">#{{ $i+1 }}</span>
                <img src="{{ $u['avatar'] ? url('public/' . $u['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                <a href="{{ url('u/' . $u['username']) }}" class="flex-grow-1">
                    <strong>{{ $u['display_name'] ?? $u['username'] }}</strong>
                    @if($u['is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif
                </a>
                <span class="badge bg-primary">Lvl {{ $u['level'] }}</span>
                @if($u['vip_level'] > 0)<span class="badge bg-warning ms-1">VIP {{ $u['vip_level'] }}</span>@endif
                <span class="text-muted small ms-2">{{ number_format($u['followers_count'] ?? 0) }} followers</span>
            </div>
            @endforeach
        @elseif($type === 'agencies')
            @foreach($data as $i => $a)
            <div class="lb-row">
                <span class="lb-rank rank-{{ $i+1 }}">#{{ $i+1 }}</span>
                <img src="{{ $a['logo'] ? url('public/' . $a['logo']) : asset('assets/images/default-agency.svg') }}" class="mini-avatar">
                <a href="{{ url('agencies/' . $a['slug']) }}" class="flex-grow-1">
                    <strong>{{ $a['name'] }}</strong>
                    @if($a['verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif
                </a>
                <span class="badge bg-primary">Lvl {{ $a['level'] }}</span>
                <span class="text-muted small ms-2">{{ number_format($a['members_count']) }} members</span>
            </div>
            @endforeach
        @else
            @foreach($data as $i => $r)
            <div class="lb-row">
                <span class="lb-rank rank-{{ $i+1 }}">#{{ $i+1 }}</span>
                <img src="{{ $r['cover'] ? url('public/' . $r['cover']) : asset('assets/images/room-default.svg') }}" class="mini-avatar" style="border-radius:8px">
                <a href="{{ url('rooms/' . $r['id']) }}" class="flex-grow-1">
                    <strong>{{ $r['name'] }}</strong>
                </a>
                <span class="text-muted small">{{ number_format($r['current_listeners']) }} listeners</span>
            </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
