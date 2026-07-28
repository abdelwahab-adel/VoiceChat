@extends('layouts.app')
@section('content')
<div class="profile-page">
    <!-- Cover -->
    <div class="profile-cover" style="background-image:url('{{ $profile['cover'] ?? asset('assets/images/cover-default.svg') }}')">
        <div class="cover-overlay"></div>
    </div>

    <div class="container">
        <div class="profile-header glass-card p-4">
            <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-3">
                <img src="{{ $profile['avatar'] ?? asset('assets/images/default-avatar.svg') }}" class="profile-avatar" alt="{{ $profile['username'] }}">
                <div class="flex-grow-1 text-center text-md-start">
                    <h2 class="mb-1">
                        {{ $profile['display_name'] }}
                        @if($profile['is_verified'])<i class="bi bi-patch-check-fill text-info"></i>@endif
                    </h2>
                    <div class="text-muted">@{{ $profile['username'] }} · Lv {{ $profile['level'] }} @if($profile['vip_level'] > 0)· VIP {{ $profile['vip_level'] }}@endif</div>
                    @if($profile['bio'])
                        <p class="mt-2 mb-0">{{ $profile['bio'] }}</p>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if($isMe)
                        <a href="{{ url('settings') }}" class="btn btn-ghost"><i class="bi bi-pencil me-1"></i> Edit profile</a>
                    @else
                        @if($user)
                            <form method="POST" action="{{ url($isFollowing ? '/unfollow/' . $profile['id'] : '/follow/' . $profile['id']) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn {{ $isFollowing ? 'btn-ghost' : 'btn-primary-gradient' }}">
                                    <i class="bi bi-{{ $isFollowing ? 'check2' : 'plus' }} me-1"></i>
                                    {{ $isFollowing ? 'Following' : 'Follow' }}
                                </button>
                            </form>
                            <a href="{{ url('messages/' . $profile['id']) }}" class="btn btn-ghost"><i class="bi bi-chat-dots"></i></a>
                            <div class="dropdown">
                                <button class="btn btn-ghost" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end glass-menu">
                                    <li>
                                        <form method="POST" action="{{ url('block/' . $profile['id']) }}" class="d-inline">
                                            @csrf
                                            <button class="dropdown-item text-warning" type="submit"><i class="bi bi-slash-circle me-2"></i>Block</button>
                                        </form>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#reportModal"><i class="bi bi-flag me-2"></i>Report</button>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Stats -->
            <div class="profile-stats row g-3 mt-3 text-center">
                <div class="col-3"><a href="{{ url('profile/' . $profile['id'] . '/followers') }}" class="text-decoration-none"><strong>{{ number_format($stats['followers']) }}</strong><br><small class="text-muted">Followers</small></a></div>
                <div class="col-3"><a href="{{ url('profile/' . $profile['id'] . '/following') }}" class="text-decoration-none"><strong>{{ number_format($stats['following']) }}</strong><br><small class="text-muted">Following</small></a></div>
                <div class="col-3"><strong>{{ number_format($stats['rooms']) }}</strong><br><small class="text-muted">Rooms</small></div>
                <div class="col-3"><strong class="text-warning">{{ number_format($stats['gifts_received']) }}</strong><br><small class="text-muted">Coins received</small></div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-modal">
            <form method="POST" action="{{ url('report/user/' . $profile['id']) }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">Report User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select mb-2" name="reason" required>
                        <option value="">Select a reason</option>
                        <option>Spam</option>
                        <option>Harassment</option>
                        <option>Impersonation</option>
                        <option>Hate speech</option>
                        <option>Inappropriate content</option>
                    </select>
                    <textarea class="form-control" name="description" placeholder="Describe the issue..." maxlength="1000"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-danger">Submit report</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
