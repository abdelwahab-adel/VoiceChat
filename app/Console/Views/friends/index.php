@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2 class="mb-4"><i class="bi bi-people me-2"></i> Friends</h2>

    @if(!empty($pending))
    <div class="glass-card p-3 mb-3">
        <h5>Pending Requests ({{ count($pending) }})</h5>
        @foreach($pending as $p)
        <div class="friend-row">
            <img src="{{ $p['avatar'] ? url('public/' . $p['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
            <a href="{{ url('u/' . $p['username']) }}" class="flex-grow-1 fw-semibold">{{ $p['display_name'] ?? $p['username'] }}</a>
            <form method="POST" action="{{ url('friends/accept/' . $p['user_id']) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> Accept</button>
            </form>
            <form method="POST" action="{{ url('friends/reject/' . $p['user_id']) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
            </form>
        </div>
        @endforeach
    </div>
    @endif

    @if(!empty($sent))
    <div class="glass-card p-3 mb-3">
        <h5>Sent Requests</h5>
        @foreach($sent as $p)
        <div class="friend-row">
            <img src="{{ $p['avatar'] ? url('public/' . $p['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
            <a href="{{ url('u/' . $p['username']) }}" class="flex-grow-1 fw-semibold">{{ $p['display_name'] ?? $p['username'] }}</a>
            <span class="badge bg-secondary">Pending</span>
        </div>
        @endforeach
    </div>
    @endif

    <div class="glass-card p-3">
        <h5>Friends ({{ count($accepted) }})</h5>
        @forelse($accepted as $f)
        <div class="friend-row">
            <img src="{{ $f['avatar'] ? url('public/' . $f['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
            <a href="{{ url('u/' . $f['username']) }}" class="flex-grow-1 fw-semibold">{{ $f['display_name'] ?? $f['username'] }} @if($f['is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif</a>
            <a href="{{ url('messages/' . $f['user_id']) }}" class="btn btn-sm btn-ghost"><i class="bi bi-chat"></i></a>
            <form method="POST" action="{{ url('friends/unfriend/' . $f['user_id']) }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Unfriend?')"><i class="bi bi-person-x"></i></button>
            </form>
        </div>
        @empty
            <p class="text-muted text-center py-3 mb-0">No friends yet. Find people to connect with!</p>
        @endforelse
    </div>
</div>
@endsection
