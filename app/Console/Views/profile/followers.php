@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h3 class="mb-4">Followers</h3>
    <div class="row g-3">
        @foreach($users as $u)
        <div class="col-6 col-md-3">
            <a href="{{ url('u/' . $u['username']) }}" class="user-mini-card">
                <img src="{{ $u['avatar'] ? url('public/' . $u['avatar']) : asset('assets/images/default-avatar.svg') }}" class="user-mini-avatar">
                <div>
                    <div class="fw-semibold">{{ $u['display_name'] ?? $u['username'] }} @if($u['is_verified'])<i class="bi bi-patch-check-fill text-info"></i>@endif</div>
                    <small class="text-muted">@{{ $u['username'] }} · Lv {{ $u['level'] }}</small>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
