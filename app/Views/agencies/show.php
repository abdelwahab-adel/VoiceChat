@extends('layouts.app')
@section('content')
<div class="agency-page">
    <div class="agency-cover" style="background-image:url('{{ $agency['cover'] ? url('public/' . $agency['cover']) : asset('assets/images/cover-default.svg') }}')"></div>
    <div class="container">
        <div class="glass-card p-4 text-center" style="margin-top:-50px; position:relative; z-index:1;">
            <img src="{{ $agency['logo'] ? url('public/' . $agency['logo']) : asset('assets/images/default-agency.svg') }}" class="agency-avatar-lg">
            <h2 class="mt-3 mb-1">
                {{ $agency['name'] }}
                @if($agency['verified'])<i class="bi bi-patch-check-fill text-info"></i>@endif
            </h2>
            <p class="text-muted">{{ $agency['description'] ?? 'No description yet.' }}</p>
            <div class="d-flex justify-content-center gap-3 mb-3">
                <div><strong>{{ number_format($agency['members_count']) }}</strong><br><small class="text-muted">Members</small></div>
                <div><strong>Lvl {{ $agency['level'] }}</strong><br><small class="text-muted">Level</small></div>
                <div><strong>{{ number_format($agency['xp']) }}</strong><br><small class="text-muted">XP</small></div>
            </div>
            @if($user && !$isMember && !$isOwner)
                <form method="POST" action="{{ url('agencies/' . $agency['id'] . '/join') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-primary-gradient"><i class="bi bi-plus-circle me-1"></i> Request to join</button>
                </form>
            @elseif($isMember)
                <span class="badge bg-success"><i class="bi bi-check2"></i> Member</span>
            @endif
        </div>

        <div class="row g-3 mt-3">
            <div class="col-lg-6">
                <div class="glass-card p-3">
                    <h5><i class="bi bi-people me-2"></i> Members</h5>
                    <div class="members-list">
                        @foreach(array_slice($members, 0, 12) as $m)
                        <a href="{{ url('u/' . $m['username']) }}" class="member-chip">
                            <img src="{{ $m['avatar'] ? url('public/' . $m['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                            <span class="small">{{ $m['display_name'] ?? $m['username'] }}</span>
                            <span class="role-badge role-{{ $m['role'] }}">{{ $m['role'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-3">
                    <h5><i class="bi bi-mic me-2"></i> Agency Rooms</h5>
                    @forelse($rooms as $r)
                    <a href="{{ url('rooms/' . $r['id']) }}" class="d-flex align-items-center gap-2 p-2 rounded hover-bg">
                        <img src="{{ $r['cover'] ? url('public/' . $r['cover']) : asset('assets/images/room-default.svg') }}" class="rounded" width="48" height="48" style="object-fit:cover">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $r['name'] }}</div>
                            <small class="text-muted">{{ $r['current_listeners'] ?? 0 }} listeners</small>
                        </div>
                    </a>
                    @empty
                        <p class="text-muted small mb-0">No rooms yet</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
