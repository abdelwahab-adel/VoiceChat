@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
    </div>
    <div class="container">
        <div class="row align-items-center hero-row">
            <div class="col-lg-7">
                <span class="hero-eyebrow"><i class="bi bi-stars"></i> Real-time voice for everyone</span>
                <h1 class="hero-title">
                    Where voices<br>
                    <span class="gradient-text">come alive.</span>
                </h1>
                <p class="hero-subtitle">
                    Step into a room, take the mic, and meet people who sound like you.
                    Voice-first communities for creators, gamers, learners & night owls.
                </p>
                <div class="hero-cta">
                    @if(!$user)
                        <a href="{{ url('register') }}" class="btn btn-primary-gradient btn-lg">
                            <i class="bi bi-rocket-takeoff me-2"></i>Start chatting free
                        </a>
                        <a href="{{ url('rooms') }}" class="btn btn-ghost btn-lg">
                            <i class="bi bi-mic me-2"></i>Browse rooms
                        </a>
                    @else
                        <a href="{{ url('rooms/create') }}" class="btn btn-primary-gradient btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>Create a room
                        </a>
                        <a href="{{ url('explore') }}" class="btn btn-ghost btn-lg">
                            <i class="bi bi-compass me-2"></i>Explore
                        </a>
                    @endif
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>{{ number_format($stats['users'] ?? 0) }}</strong>
                        <span>Members</span>
                    </div>
                    <div class="hero-stat">
                        <strong>{{ number_format($stats['rooms'] ?? 0) }}</strong>
                        <span>Rooms</span>
                    </div>
                    <div class="hero-stat">
                        <strong><i class="bi bi-circle-fill text-success live-dot"></i> {{ number_format($stats['online'] ?? 0) }}</strong>
                        <span>Online now</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-visual">
                    <div class="visual-card visual-card-1">
                        <div class="visual-mic">
                            <div class="mic-pulse"></div>
                            <i class="bi bi-mic-fill"></i>
                        </div>
                        <div>
                            <strong>Live now</strong>
                            <small class="d-block text-muted">Welcome Lounge</small>
                        </div>
                        <span class="badge bg-success">3.2K</span>
                    </div>
                    <div class="visual-card visual-card-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-stack">
                                <span class="avatar-mini" style="background:#5e3eff">S</span>
                                <span class="avatar-mini" style="background:#ff5e8a">A</span>
                                <span class="avatar-mini" style="background:#1ed760">M</span>
                            </div>
                            <div>
                                <strong>Chill Vibes 🌙</strong>
                                <small class="d-block text-muted">Late Night Talks</small>
                            </div>
                        </div>
                    </div>
                    <div class="visual-card visual-card-3">
                        <strong>🎁 Galaxy</strong>
                        <small class="text-muted">Sent 12s ago</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Announcements -->
@if(!empty($announcements))
<section class="container my-4">
    @foreach($announcements as $a)
        <div class="announcement-bar announcement-{{ $a['type'] }}">
            <i class="bi bi-megaphone-fill me-2"></i>
            <strong>{{ $a['title'] }}</strong>
            <span class="d-none d-md-inline ms-2">{{ $a['body'] }}</span>
        </div>
    @endforeach
</section>
@endif

<!-- Featured Rooms -->
<section class="container my-5">
    <div class="section-head">
        <div>
            <h2 class="section-title">🔥 Featured Rooms</h2>
            <p class="section-sub">Jump into the most popular live rooms right now</p>
        </div>
        <a href="{{ url('rooms') }}" class="btn btn-ghost btn-sm">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-3">
        @forelse($rooms as $room)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ url('rooms/' . $room['id']) }}" class="room-card">
                    <div class="room-cover" style="background-image:url('{{ $room['cover'] ? url('public/' . $room['cover']) : asset('assets/images/room-default.svg') }}')">
                        <span class="room-badge">LIVE</span>
                        <span class="room-listeners"><i class="bi bi-people-fill"></i> {{ number_format($room['current_listeners'] ?? 0) }}</span>
                    </div>
                    <div class="room-info">
                        <h6 class="room-title">{{ $room['name'] }}</h6>
                        <div class="room-meta">
                            <span class="text-truncate">{{ $room['owner_display_name'] ?? $room['owner_username'] }}</span>
                            @if($room['type'] === 'password')<i class="bi bi-lock-fill text-muted ms-1" title="Password protected"></i>@endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">No rooms yet. Be the first to create one!</div>
        @endforelse
    </div>
</section>

<!-- Agencies -->
<section class="container my-5">
    <div class="section-head">
        <div>
            <h2 class="section-title">⭐ Top Agencies</h2>
            <p class="section-sub">Communities that produce the best content</p>
        </div>
        <a href="{{ url('agencies') }}" class="btn btn-ghost btn-sm">View all <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <div class="row g-3">
        @foreach($agencies as $a)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ url('agencies/' . $a['slug']) }}" class="agency-card">
                    <div class="agency-logo">
                        <img src="{{ $a['logo'] ? url('public/' . $a['logo']) : asset('assets/images/default-agency.svg') }}" alt="{{ $a['name'] }}">
                    </div>
                    <div class="agency-body">
                        <div class="d-flex align-items-center gap-1">
                            <h6 class="agency-name mb-0">{{ $a['name'] }}</h6>
                            @if($a['verified'])<i class="bi bi-patch-check-fill text-info"></i>@endif
                        </div>
                        <small class="text-muted">{{ number_format($a['members_count'] ?? 0) }} members</small>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</section>

<!-- Features -->
<section class="container my-5">
    <div class="row g-4 text-center">
        <div class="col-md-3 col-6">
            <div class="feature-pill"><i class="bi bi-mic-fill"></i></div>
            <h6 class="mt-3">HD Voice</h6>
            <p class="text-muted small">Crystal-clear audio with echo cancellation</p>
        </div>
        <div class="col-md-3 col-6">
            <div class="feature-pill"><i class="bi bi-people-fill"></i></div>
            <h6 class="mt-3">8–16 Mics</h6>
            <p class="text-muted small">Host up to 16 speakers per room</p>
        </div>
        <div class="col-md-3 col-6">
            <div class="feature-pill"><i class="bi bi-gift-fill"></i></div>
            <h6 class="mt-3">Live Gifts</h6>
            <p class="text-muted small">Animated gifts with full effects</p>
        </div>
        <div class="col-md-3 col-6">
            <div class="feature-pill"><i class="bi bi-shield-check"></i></div>
            <h6 class="mt-3">Safe & Secure</h6>
            <p class="text-muted small">Active moderation and reporting</p>
        </div>
    </div>
</section>
@endsection
