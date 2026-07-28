@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:900px;">
    <a href="{{ url('admin/users') }}" class="text-decoration-none small">← Back to users</a>
    <div class="glass-card p-4 mt-2">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img src="{{ $userData['avatar'] ? url('public/' . $userData['avatar']) : asset('assets/images/default-avatar.svg') }}" class="rounded-circle" width="80" height="80">
            <div>
                <h3 class="mb-0">{{ $userData['display_name'] ?? $userData['username'] }}</h3>
                <small class="text-muted">@{{ $userData['username'] }} · {{ $userData['email'] }}</small>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="stat-mini"><strong>{{ $stats['followers'] }}</strong><br><small class="text-muted">Followers</small></div></div>
            <div class="col-md-3"><div class="stat-mini"><strong>{{ $stats['rooms'] }}</strong><br><small class="text-muted">Rooms</small></div></div>
            <div class="col-md-3"><div class="stat-mini"><strong>{{ number_format($stats['gifts_sent']) }}</strong><br><small class="text-muted">Sent coins</small></div></div>
            <div class="col-md-3"><div class="stat-mini"><strong>{{ number_format($stats['gifts_received']) }}</strong><br><small class="text-muted">Received coins</small></div></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <h5>Edit User</h5>
                <form method="POST" action="{{ url('admin/users/' . $userData['id'] . '/update') }}">
                    @csrf
                    <div class="form-floating mb-2"><input type="text" class="form-control" name="display_name" value="{{ $userData['display_name'] }}"><label>Display name</label></div>
                    <div class="form-floating mb-2"><textarea class="form-control" name="bio" style="height:80px">{{ $userData['bio'] }}</textarea><label>Bio</label></div>
                    <div class="mb-2">
                        <label class="form-label small">Role</label>
                        <select class="form-select" name="role">
                            @foreach(['user','moderator','admin','superadmin'] as $r)
                                <option value="{{ $r }}" @if($userData['role'] === $r) selected @endif>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Status</label>
                        <select class="form-select" name="status">
                            @foreach(['active','suspended','banned','pending'] as $s)
                                <option value="{{ $s }}" @if($userData['status'] === $s) selected @endif>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="v" @if($userData['is_verified']) checked @endif>
                        <label class="form-check-label" for="v">Verified badge</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="f" @if($userData['is_featured']) checked @endif>
                        <label class="form-check-label" for="f">Featured user</label>
                    </div>
                    <button class="btn btn-primary-gradient">Save changes</button>
                </form>
            </div>

            <div class="col-md-6">
                <h5>Actions</h5>
                <form method="POST" action="{{ url('admin/users/' . $userData['id'] . '/coins') }}" class="mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-8"><input type="number" class="form-control" name="amount" placeholder="Amount" min="1" required></div>
                        <div class="col-4"><button class="btn btn-warning w-100">+ Coins</button></div>
                    </div>
                    <input type="text" class="form-control mt-2" name="note" placeholder="Note (optional)">
                </form>

                <button class="btn btn-outline-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#banModal"><i class="bi bi-shield-x me-1"></i> Ban User</button>
                @if($userData['status'] === 'banned')
                    <form method="POST" action="{{ url('admin/users/' . $userData['id'] . '/unban') }}">
                        @csrf
                        <button class="btn btn-success w-100"><i class="bi bi-shield-check me-1"></i> Unban</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="banModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content glass-modal">
            <form method="POST" action="{{ url('admin/users/' . $userData['id'] . '/ban') }}">
                @csrf
                <div class="modal-header border-0"><h5>Ban user</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="text" name="reason" class="form-control mb-2" placeholder="Reason" required>
                    <select class="form-select mb-2" name="type">
                        <option value="temporary">Temporary</option>
                        <option value="permanent">Permanent</option>
                    </select>
                    <input type="number" name="days" class="form-control" placeholder="Days (if temporary)" value="7">
                </div>
                <div class="modal-footer border-0"><button class="btn btn-danger">Ban</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
