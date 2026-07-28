@extends('layouts.app')

@section('head')
<style>
.auth-shell { min-height: calc(100vh - 80px); display:flex; align-items:center; justify-content:center; padding:2rem 1rem; position:relative; overflow:hidden; }
.auth-shell::before { content:''; position:absolute; width:600px; height:600px; background:radial-gradient(circle, rgba(94,62,255,0.3) 0%, transparent 70%); top:-200px; left:-200px; pointer-events:none; }
.auth-shell::after { content:''; position:absolute; width:600px; height:600px; background:radial-gradient(circle, rgba(255,94,138,0.2) 0%, transparent 70%); bottom:-200px; right:-200px; pointer-events:none; }
.auth-card { background:rgba(20,25,35,0.85); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:3rem 2.5rem; max-width:440px; width:100%; box-shadow:0 30px 80px rgba(0,0,0,0.4); position:relative; z-index:1; }
.auth-title { font-size:2rem; font-weight:700; margin-bottom:0.5rem; }
.auth-sub { color:#a4abbd; margin-bottom:2rem; }
</style>
@endsection

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3" style="width:60px; height:60px; font-size:1.5rem;"><i class="bi bi-soundwave"></i></div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-sub">Sign in to continue your voice journey</p>
        </div>

        <form method="POST" action="{{ url('login') }}">
            @csrf
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="login" name="login" placeholder="Username or Email" required>
                <label for="login">Username or Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="{{ url('forgot-password') }}" class="text-decoration-none small">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary-gradient w-100 btn-lg mb-3">Sign in</button>
            <div class="text-center text-muted small">
                Don't have an account? <a href="{{ url('register') }}" class="text-decoration-none fw-semibold">Create one</a>
            </div>
        </form>
    </div>
</div>
@endsection
