@extends('layouts.app')
@section('content')
<div class="container my-4">
    <div class="d-flex align-items-center gap-3 mb-4">
        <img src="{{ $agency['logo'] ? url('public/' . $agency['logo']) : asset('assets/images/default-agency.svg') }}" width="60" height="60" class="rounded-circle">
        <div>
            <h3 class="mb-0">{{ $agency['name'] }} <small class="text-muted">/ Members</small></h3>
        </div>
    </div>
    <div class="glass-card p-3">
        <div class="row g-2">
            @foreach($members as $m)
            <div class="col-md-4">
                <a href="{{ url('u/' . $m['username']) }}" class="member-row">
                    <img src="{{ $m['avatar'] ? url('public/' . $m['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $m['display_name'] ?? $m['username'] }}</div>
                        <small class="text-muted">{{ ucfirst($m['role']) }} · Lv {{ $m['level'] }}</small>
                    </div>
                    @if($m['is_verified'])<i class="bi bi-patch-check-fill text-info"></i>@endif
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
