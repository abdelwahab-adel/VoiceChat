@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2>Gift Leaderboard</h2>
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#topReceived">Top Received</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#topSent">Top Sent</a></li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="topReceived">
            <div class="glass-card p-3">
                @foreach($topReceived as $i => $u)
                <div class="lb-row">
                    <span class="lb-rank rank-{{ $i+1 }}">#{{ $i+1 }}</span>
                    <img src="{{ $u['avatar'] ? url('public/' . $u['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                    <a href="{{ url('u/' . $u['username']) }}" class="flex-grow-1 fw-semibold">{{ $u['display_name'] ?? $u['username'] }}</a>
                    <span class="text-warning">{{ number_format($u['total_coins']) }} <i class="bi bi-coin"></i></span>
                </div>
                @endforeach
            </div>
        </div>
        <div class="tab-pane fade" id="topSent">
            <div class="glass-card p-3">
                @foreach($topSent as $i => $u)
                <div class="lb-row">
                    <span class="lb-rank rank-{{ $i+1 }}">#{{ $i+1 }}</span>
                    <img src="{{ $u['avatar'] ? url('public/' . $u['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                    <a href="{{ url('u/' . $u['username']) }}" class="flex-grow-1 fw-semibold">{{ $u['display_name'] ?? $u['username'] }}</a>
                    <span class="text-warning">{{ number_format($u['total_coins']) }} <i class="bi bi-coin"></i></span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
