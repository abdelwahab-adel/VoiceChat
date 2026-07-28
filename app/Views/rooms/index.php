@extends('layouts.app')
@section('content')
<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Voice Rooms</h2>
            <p class="text-muted mb-0">Discover live conversations happening right now</p>
        </div>
        @if($user)
        <a href="{{ url('rooms/create') }}" class="btn btn-primary-gradient"><i class="bi bi-plus-lg me-1"></i> Create Room</a>
        @endif
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="search" name="q" class="form-control" placeholder="Search rooms..." value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                @foreach(['general','music','gaming','education','talk','dating','entertainment'] as $c)
                    <option value="{{ $c }}" @if(($filters['category'] ?? '') === $c) selected @endif>{{ ucfirst($c) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="type">
                <option value="">All types</option>
                <option value="public" @if(($filters['type'] ?? '') === 'public') selected @endif>Public</option>
                <option value="password" @if(($filters['type'] ?? '') === 'password') selected @endif>Password</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-ghost w-100" type="submit"><i class="bi bi-funnel me-1"></i> Filter</button>
        </div>
    </form>

    <div class="row g-3">
        @forelse($rooms as $room)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ url('rooms/' . $room['id']) }}" class="room-card">
                    <div class="room-cover" style="background-image:url('{{ $room['cover'] ? url('public/' . $room['cover']) : asset('assets/images/room-default.svg') }}')">
                        <span class="room-badge">LIVE</span>
                        @if($room['is_featured'])<span class="room-featured"><i class="bi bi-star-fill"></i></span>@endif
                        <span class="room-listeners"><i class="bi bi-people-fill"></i> {{ number_format($room['current_listeners'] ?? 0) }}</span>
                    </div>
                    <div class="room-info">
                        <h6 class="room-title text-truncate">{{ $room['name'] }}</h6>
                        <div class="d-flex align-items-center gap-1 small text-muted">
                            <img src="{{ $room['owner_avatar'] ? url('public/' . $room['owner_avatar']) : asset('assets/images/default-avatar.svg') }}" class="owner-mini-avatar">
                            <span class="text-truncate">{{ $room['owner_display_name'] ?? $room['owner_username'] }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-mic-mute display-1 text-muted opacity-25"></i>
                <h4 class="mt-3">No rooms found</h4>
                <p class="text-muted">Try adjusting your filters or create the first one!</p>
            </div>
        @endforelse
    </div>

    @if(($pager['last_page'] ?? 1) > 1)
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item @if($pager['current_page'] <= 1) disabled @endif">
                <a class="page-link" href="?page={{ $pager['current_page'] - 1 }}">Previous</a>
            </li>
            <li class="page-item active"><span class="page-link">{{ $pager['current_page'] }} / {{ $pager['last_page'] }}</span></li>
            <li class="page-item @if($pager['current_page'] >= $pager['last_page']) disabled @endif">
                <a class="page-link" href="?page={{ $pager['current_page'] + 1 }}">Next</a>
            </li>
        </ul>
    </nav>
    @endif
</div>
@endsection
