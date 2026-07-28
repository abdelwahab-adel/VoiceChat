@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2><i class="bi bi-search me-2"></i> Search</h2>
    <form method="GET" class="mb-4">
        <input type="search" name="q" class="form-control form-control-lg" placeholder="Search people, rooms, agencies..." value="{{ $q }}" autofocus>
    </form>

    @if($q === '')
        <p class="text-muted text-center py-5">Start typing to search…</p>
    @else
        <div class="row g-4">
            <div class="col-lg-4">
                <h5>Users <span class="badge bg-primary-subtle text-primary">{{ count($results['users']) }}</span></h5>
                @foreach($results['users'] as $u)
                <a href="{{ url('u/' . $u['username']) }}" class="search-result">
                    <img src="{{ $u['avatar'] ? url('public/' . $u['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                    <div>
                        <div class="fw-semibold">{{ $u['display_name'] ?? $u['username'] }} @if($u['is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif</div>
                        <small class="text-muted">@{{ $u['username'] }} · Lv {{ $u['level'] }}</small>
                    </div>
                </a>
                @endforeach
            </div>
            <div class="col-lg-4">
                <h5>Rooms <span class="badge bg-primary-subtle text-primary">{{ count($results['rooms']) }}</span></h5>
                @foreach($results['rooms'] as $r)
                <a href="{{ url('rooms/' . $r['id']) }}" class="search-result">
                    <img src="{{ $r['cover'] ? url('public/' . $r['cover']) : asset('assets/images/room-default.svg') }}" class="mini-avatar" style="border-radius:8px">
                    <div>
                        <div class="fw-semibold">{{ $r['name'] }}</div>
                        <small class="text-muted">{{ $r['current_listeners'] ?? 0 }} listeners</small>
                    </div>
                </a>
                @endforeach
            </div>
            <div class="col-lg-4">
                <h5>Agencies <span class="badge bg-primary-subtle text-primary">{{ count($results['agencies']) }}</span></h5>
                @foreach($results['agencies'] as $a)
                <a href="{{ url('agencies/' . $a['slug']) }}" class="search-result">
                    <img src="{{ $a['logo'] ? url('public/' . $a['logo']) : asset('assets/images/default-agency.svg') }}" class="mini-avatar">
                    <div>
                        <div class="fw-semibold">{{ $a['name'] }} @if($a['verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif</div>
                        <small class="text-muted">{{ $a['members_count'] }} members</small>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
