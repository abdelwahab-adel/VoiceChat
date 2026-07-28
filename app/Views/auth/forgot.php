@extends('layouts.app')
@section('content')
<div class="container py-5" style="max-width:480px;">
    <div class="glass-card p-4">
        <h2>Forgot your password?</h2>
        <p class="text-muted">Enter your email and we'll send you a reset link.</p>
        <form method="POST" action="{{ url('forgot-password') }}">
            @csrf
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <button type="submit" class="btn btn-primary-gradient w-100">Send reset link</button>
        </form>
        <p class="text-center text-muted small mt-3"><a href="{{ url('login') }}">Back to sign in</a></p>
    </div>
</div>
@endsection
