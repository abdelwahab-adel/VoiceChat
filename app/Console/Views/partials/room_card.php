<a href="{{ url('rooms/' . $room['id']) }}" class="room-card">
    <div class="room-cover" style="background-image:url('{{ isset($room['cover']) && $room['cover'] ? url('public/' . $room['cover']) : asset('assets/images/room-default.svg') }}')">
        <span class="room-badge">LIVE</span>
        @if(!empty($room['is_featured']))<span class="room-featured"><i class="bi bi-star-fill"></i></span>@endif
        <span class="room-listeners"><i class="bi bi-people-fill"></i> {{ number_format($room['current_listeners'] ?? 0) }}</span>
    </div>
    <div class="room-info">
        <h6 class="room-title text-truncate">{{ $room['name'] }}</h6>
        <div class="d-flex align-items-center gap-1 small text-muted">
            <img src="{{ !empty($room['owner_avatar']) ? url('public/' . $room['owner_avatar']) : asset('assets/images/default-avatar.svg') }}" class="owner-mini-avatar">
            <span class="text-truncate">{{ $room['owner_display_name'] ?? $room['owner_username'] ?? '' }}</span>
        </div>
    </div>
</a>
