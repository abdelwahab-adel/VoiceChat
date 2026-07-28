@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:760px;">
    <h2 class="mb-4"><i class="bi bi-gear me-2"></i> Settings</h2>

    <div class="glass-card p-4 mb-3">
        <h5>Profile</h5>
        <form method="POST" action="{{ url('settings/update') }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="display_name" name="display_name" value="{{ $userData['display_name'] ?? '' }}">
                        <label>Display name</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="language" name="language" value="{{ $userData['language'] ?? 'en' }}">
                        <label>Language</label>
                    </div>
                </div>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="bio" name="bio" style="height:80px" maxlength="500">{{ $userData['bio'] ?? '' }}</textarea>
                <label>Bio</label>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="country" name="country" value="{{ $userData['country'] ?? '' }}">
                        <label>Country</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <select class="form-select mb-3" name="gender">
                        <option value="">Gender</option>
                        <option value="male" @if(($userData['gender'] ?? '') === 'male') selected @endif>Male</option>
                        <option value="female" @if(($userData['gender'] ?? '') === 'female') selected @endif>Female</option>
                        <option value="other" @if(($userData['gender'] ?? '') === 'other') selected @endif>Other</option>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary-gradient">Save profile</button>
        </form>
    </div>

    <div class="glass-card p-4 mb-3">
        <h5>Notification preferences</h5>
        <form method="POST" action="{{ url('settings/notifications') }}">
            @csrf
            @php $notif = $settings['notifications'] ?? []; @endphp
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="n1" name="notif_friend" value="1" @if(!empty($notif['notif_friend'])) checked @endif>
                <label class="form-check-label" for="n1">Friend requests</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="n2" name="notif_message" value="1" @if(!empty($notif['notif_message'])) checked @endif>
                <label class="form-check-label" for="n2">New messages</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="n3" name="notif_gift" value="1" @if(!empty($notif['notif_gift'])) checked @endif>
                <label class="form-check-label" for="n3">Gifts received</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="n4" name="notif_room" value="1" @if(!empty($notif['notif_room'])) checked @endif>
                <label class="form-check-label" for="n4">Room invites</label>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="n5" name="notif_announce" value="1" @if(!empty($notif['notif_announce'])) checked @endif>
                <label class="form-check-label" for="n5">Announcements</label>
            </div>
            <button class="btn btn-primary-gradient mt-2">Save preferences</button>
        </form>
    </div>

    <div class="glass-card p-4 mb-3">
        <h5>Privacy</h5>
        <form method="POST" action="{{ url('settings/privacy') }}">
            @csrf
            @php $priv = $settings['privacy'] ?? []; @endphp
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="p1" name="show_online" value="1" @if(!empty($priv['show_online'])) checked @endif>
                <label class="form-check-label" for="p1">Show my online status</label>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted">Who can message me</label>
                <select class="form-select" name="allow_messages">
                    <option value="everyone" @if(($priv['allow_messages'] ?? '') === 'everyone') selected @endif>Everyone</option>
                    <option value="friends" @if(($priv['allow_messages'] ?? '') === 'friends') selected @endif>Friends only</option>
                    <option value="nobody" @if(($priv['allow_messages'] ?? '') === 'nobody') selected @endif>Nobody</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted">Who can invite me to rooms</label>
                <select class="form-select" name="allow_invites">
                    <option value="everyone" @if(($priv['allow_invites'] ?? '') === 'everyone') selected @endif>Everyone</option>
                    <option value="friends" @if(($priv['allow_invites'] ?? '') === 'friends') selected @endif>Friends only</option>
                    <option value="nobody" @if(($priv['allow_invites'] ?? '') === 'nobody') selected @endif>Nobody</option>
                </select>
            </div>
            <button class="btn btn-primary-gradient">Save privacy</button>
        </form>
    </div>

    <div class="glass-card p-4">
        <h5>Change password</h5>
        <form method="POST" action="{{ url('profile/password') }}">
            @csrf
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="current_password" name="current_password" required>
                <label>Current password</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                <label>New password</label>
            </div>
            <button class="btn btn-warning">Update password</button>
        </form>
    </div>
</div>
@endsection
