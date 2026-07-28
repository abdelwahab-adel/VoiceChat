@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2>{{ ucfirst($type) }} matching "{{ $q }}"</h2>
    <div class="glass-card p-3">
        @forelse($results as $r)
            @if($type === 'users')
                <a href="{{ url('u/' . $r['username']) }}" class="search-result">
                    <img src="{{ $r['avatar'] ? url('public/' . $r['avatar']) : asset('assets/images/default-avatar.svg') }}" class="mini-avatar">
                    <div class="flex-grow-1">
                        <strong>{{ $r['display_name'] ?? $r['username'] }}</strong>
                        <small class="text-muted ms-2">@{{ $r['username'] }} · Lv {{ $r['level'] }}</small>
                    </div>
                </a>
            @elseif($type === 'rooms')
                <a href="{{ url('rooms/' . $r['id']) }}" class="search-result">
                    <img src="{{ $r['cover'] ? url('public/' . $r['cover']) : asset('assets/images/room-default.svg') }}" class="mini-avatar" style="border-radius:8px">
                    <div class="flex-grow-1">
                        <strong>{{ $r['name'] }}</strong>
                        <small class="text-muted ms-2">{{ $r['current_listeners'] ?? 0 }} listeners</small>
                    </div>
                </a>
            @else
                <a href="{{ url('agencies/' . ($r['slug'] ?? $r['id'])) }}" class="search-result">
                    <img src="{{ $r['logo'] ? url('public/' . $r['logo']) : asset('assets/images/default-agency.svg') }}" class="mini-avatar">
                    <div class="flex-grow-1">
                        <strong>{{ $r['name'] }}</strong>
                        <small class="text-muted ms-2">{{ $r['members_count'] ?? 0 }} members</small>
                    </div>
                </a>
            @endif
        @empty
            <p class="text-center text-muted py-4 mb-0">No results</p>
        @endforelse
    </div>
</div>
@endsection
