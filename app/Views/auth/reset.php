@extends('layouts.app')
@section('content')
<div class="container py-5" style="max-width:480px;">
    <div class="glass-card p-4">
        <h2>Reset your password</h2>
        <form method="POST" action="{{ url('reset-password') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="New password" minlength="6" required>
                <label for="password">New password</label>
            </div>
            <button type="submit" class="btn btn-primary-gradient w-100">Reset password</button>
        </form>
    </div>
</div>
@endsection
