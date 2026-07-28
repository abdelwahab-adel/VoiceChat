@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2 class="mb-3"><i class="bi bi-compass me-2"></i> Explore</h2>
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-6"><input type="search" name="q" class="form-control" placeholder="Search rooms..." value="{{ $search ?? '' }}"></div>
        <div class="col-md-3">
            <select class="form-select" name="category">
                <option value="">All categories</option>
                @foreach(['general','music','gaming','education','talk','dating','entertainment'] as $c)
                    <option value="{{ $c }}" @if(($category ?? '') === $c) selected @endif>{{ ucfirst($c) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary-gradient w-100">Search</button></div>
    </form>

    <div class="row g-3">
        @forelse($rooms as $room)
            <div class="col-6 col-md-4 col-lg-3">
                @include('partials.room_card', ['room' => $room])
            </div>
        @empty
            <div class="col-12 text-center py-5 text-muted">No rooms found</div>
        @endforelse
    </div>
</div>
@endsection
