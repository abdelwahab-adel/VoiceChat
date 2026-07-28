<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f1419">
    <meta name="csrf-token" content="{{ $csrf }}">
    <meta name="user-id" content="{{ $user['id'] ?? '' }}">
    <meta name="ws-url" content="{{ $_ENV['WS_URL'] ?? 'ws://localhost:8080' }}">
    <title>{{ $title ?? 'VoiceChat' }} — {{ $config['name'] ?? 'VoiceChat' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon.svg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    @yield('head')
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-nav">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ url('') }}">
                <div class="brand-icon">
                    <i class="bi bi-soundwave"></i>
                </div>
                <span class="brand-text">VoiceChat</span>
            </a>

            <div class="d-flex align-items-center order-lg-2 ms-auto">
                @if($user)
                    <button class="btn btn-icon me-2 d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#searchDrawer">
                        <i class="bi bi-search"></i>
                    </button>

                    <a href="{{ url('notifications') }}" class="btn btn-icon me-1 position-relative" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="badge-dot" id="notifBadge" style="display:none"></span>
                    </a>

                    <a href="{{ url('messages') }}" class="btn btn-icon me-1 position-relative" title="Messages">
                        <i class="bi bi-chat-dots"></i>
                        <span class="badge-dot" id="msgBadge" style="display:none"></span>
                    </a>

                    <div class="coin-pill me-2 d-none d-md-flex">
                        <i class="bi bi-coin"></i>
                        <span>{{ number_format($user['coins'] ?? 0) }}</span>
                    </div>

                    <div class="dropdown">
                        <button class="btn user-pill dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="{{ $user['avatar'] ? url('public/' . $user['avatar']) : asset('assets/images/default-avatar.svg') }}"
                                 alt="{{ $user['username'] }}" class="user-avatar">
                            <span class="d-none d-md-inline ms-2">{{ $user['display_name'] ?? $user['username'] }}</span>
                            @if($user['level'] >= 10)<i class="bi bi-patch-check-fill text-info ms-1"></i>@endif
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end glass-menu">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $user['avatar'] ? url('public/' . $user['avatar']) : asset('assets/images/default-avatar.svg') }}" class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <div class="fw-semibold">{{ $user['display_name'] ?? $user['username'] }}</div>
                                        <small class="text-muted">@{{ $user['username'] }} · Lv {{ $user['level'] ?? 1 }}</small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('u/' . $user['username']) }}"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="{{ url('friends') }}"><i class="bi bi-people me-2"></i>Friends</a></li>
                            <li><a class="dropdown-item" href="{{ url('gifts/history') }}"><i class="bi bi-gift me-2"></i>My Gifts</a></li>
                            <li><a class="dropdown-item" href="{{ url('settings') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                            @if(($user['role'] ?? 'user') === 'admin' || ($user['role'] ?? 'user') === 'superadmin')
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-warning" href="{{ url('admin') }}"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ url('logout') }}" class="d-inline">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="btn btn-ghost me-2" href="{{ url('login') }}">Sign in</a>
                    <a class="btn btn-primary-gradient" href="{{ url('register') }}">Get started</a>
                @endif
            </div>

            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse order-lg-1" id="mainNav">
                <form class="d-flex ms-3 search-form" action="{{ url('search') }}" method="GET">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" name="q" class="form-control" placeholder="Search people, rooms, agencies..." value="{{ $_GET['q'] ?? '' }}">
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Mobile side menu -->
    <div class="offcanvas offcanvas-start glass-offcanvas" tabindex="-1" id="sideMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <a class="side-link" href="{{ url('') }}"><i class="bi bi-house"></i> Home</a>
            <a class="side-link" href="{{ url('explore') }}"><i class="bi bi-compass"></i> Explore</a>
            <a class="side-link" href="{{ url('rooms') }}"><i class="bi bi-mic"></i> Voice Rooms</a>
            <a class="side-link" href="{{ url('agencies') }}"><i class="bi bi-buildings"></i> Agencies</a>
            <a class="side-link" href="{{ url('leaderboard') }}"><i class="bi bi-trophy"></i> Leaderboard</a>
            <a class="side-link" href="{{ url('gifts') }}"><i class="bi bi-gift"></i> Gifts</a>
            <hr class="border-secondary opacity-25">
            @if($user)
                <a class="side-link" href="{{ url('messages') }}"><i class="bi bi-chat-dots"></i> Messages</a>
                <a class="side-link" href="{{ url('notifications') }}"><i class="bi bi-bell"></i> Notifications</a>
                <a class="side-link" href="{{ url('friends') }}"><i class="bi bi-people"></i> Friends</a>
                <a class="side-link" href="{{ url('settings') }}"><i class="bi bi-gear"></i> Settings</a>
            @endif
        </div>
    </div>

    <main class="main-content">
        @if(!empty($flash))
            <div class="container mt-3">
                @foreach($flash as $type => $messages)
                    @foreach((array)$messages as $msg)
                        <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show glass-alert" role="alert">
                            {{ $msg }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>
