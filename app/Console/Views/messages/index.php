@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:760px;">
    <h2 class="mb-3"><i class="bi bi-chat-dots me-2"></i> Messages</h2>
    <div class="glass-card p-3">
        @forelse($inbox as $conv)
        <a href="{{ url('messages/' . $conv['partner_id']) }}" class="message-row">
            <img src="{{ $conv['partner_avatar'] ? url('public/' . $conv['partner_avatar']) : asset('assets/images/default-avatar.svg') }}" class="msg-avatar">
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2">
                    <strong>{{ $conv['partner_display_name'] ?? $conv['partner_username'] }}</strong>
                    @if($conv['partner_is_verified'] ?? false)<i class="bi bi-patch-check-fill text-info small"></i>@endif
                    <small class="text-muted ms-auto">{{ time_ago($conv['last_message_at'] ?? null) }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small text-truncate">{{ $conv['last_message_content'] ?? 'Start a conversation…' }}</span>
                    @if(($conv['unread_count'] ?? 0) > 0)
                        <span class="badge bg-primary rounded-pill ms-auto">{{ $conv['unread_count'] }}</span>
                    @endif
                </div>
            </div>
        </a>
        @empty
            <div class="text-center py-5">
                <i class="bi bi-chat-square-text display-1 text-muted opacity-25"></i>
                <p class="text-muted mt-2">No conversations yet</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
