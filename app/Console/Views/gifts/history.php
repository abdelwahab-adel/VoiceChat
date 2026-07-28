@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2 class="mb-1">Gift History</h2>
    <p class="text-muted">Track all gifts you {{ $direction === 'sent' ? 'sent' : 'received' }}</p>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ $direction === 'received' ? 'active' : '' }}" href="?direction=received">Received</a></li>
        <li class="nav-item"><a class="nav-link {{ $direction === 'sent' ? 'active' : '' }}" href="?direction=sent">Sent</a></li>
    </ul>

    <div class="glass-card p-3">
        @forelse($history as $h)
        <div class="gift-row">
            @if($h['gift_image'])
                <img src="{{ url('public/' . $h['gift_image']) }}" class="gift-row-icon">
            @else
                <div class="gift-row-icon placeholder">🎁</div>
            @endif
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ $h['quantity'] }}x {{ $h['gift_name'] }}</div>
                <small class="text-muted">
                    {{ $direction === 'sent' ? 'to @' . $h['receiver_username'] : 'from @' . $h['sender_username'] }}
                    · {{ time_ago($h['created_at']) }}
                </small>
            </div>
            <div class="text-end">
                <div class="fw-semibold text-warning">{{ number_format($h['coins_total']) }} <i class="bi bi-coin"></i></div>
            </div>
        </div>
        @empty
            <p class="text-muted text-center py-4 mb-0">No gifts yet</p>
        @endforelse
    </div>
</div>
@endsection
