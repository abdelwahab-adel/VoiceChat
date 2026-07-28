@extends('layouts.app')

@section('head')
<style>
.auth-shell { min-height: calc(100vh - 80px); display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }
.auth-card { background:rgba(20,25,35,0.85); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.08); border-radius:24px; padding:2.5rem 2rem; max-width:480px; width:100%; }
</style>
@endsection

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        <div class="text-center mb-4">
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-sub">Join thousands of voices around the world</p>
        </div>

        <form method="POST" action="{{ url('register') }}">
            @csrf
            <div class="row g-2">
                <div class="col-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" pattern="[a-zA-Z0-9_]{3,30}" required>
                        <label for="username">Username</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="display_name" name="display_name" placeholder="Display name">
                        <label for="display_name">Display name</label>
                    </div>
                </div>
            </div>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="6" required>
                <label for="password">Password (min 6 chars)</label>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <select class="form-select mb-3" name="gender" id="gender">
                        <option value="">Gender (optional)</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" class="form-control mb-3" name="country" id="country" placeholder="Country (optional)">
                </div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label small" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            <button type="submit" class="btn btn-primary-gradient w-100 btn-lg mb-3">Create account</button>
            <div class="text-center text-muted small">
                Already have an account? <a href="{{ url('login') }}" class="text-decoration-none fw-semibold">Sign in</a>
            </div>
        </form>
    </div>
</div>
@endsection
