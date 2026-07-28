@extends('layouts.app')
@section('content')
<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 class="mb-1">Agencies</h2>
            <p class="text-muted mb-0">Top creator communities</p>
        </div>
        @if($user)
        <a href="{{ url('agencies/create') }}" class="btn btn-primary-gradient"><i class="bi bi-plus-lg me-1"></i> Create Agency</a>
        @endif
    </div>

    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-10">
            <input type="search" name="q" class="form-control" placeholder="Search agencies..." value="{{ $_GET['q'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-ghost w-100">Search</button>
        </div>
    </form>

    <div class="row g-3">
        @forelse($agencies as $a)
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ url('agencies/' . $a['slug']) }}" class="agency-card-lg">
                <div class="agency-logo-lg">
                    <img src="{{ $a['logo'] ? url('public/' . $a['logo']) : asset('assets/images/default-agency.svg') }}" alt="{{ $a['name'] }}">
                    @if($a['verified'])<span class="verified-badge"><i class="bi bi-patch-check-fill"></i></span>@endif
                </div>
                <h6 class="mt-3 mb-1">{{ $a['name'] }}</h6>
                <small class="text-muted">Lvl {{ $a['level'] }} · {{ number_format($a['members_count']) }} members</small>
            </a>
        </div>
        @empty
        <div class="col-12 text-center py-5 text-muted">No agencies found</div>
        @endforelse
    </div>
</div>
@endsection
