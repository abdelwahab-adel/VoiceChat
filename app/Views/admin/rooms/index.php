@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2><i class="bi bi-mic me-2"></i> Rooms</h2>
    <form method="GET" class="row g-2 my-3">
        <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search by name" value="{{ $q }}"></div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All</option>
                @foreach(['active','paused','closed','banned'] as $s)
                    <option value="{{ $s }}" @if(($status ?? '') === $s) selected @endif>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-ghost w-100">Filter</button></div>
    </form>

    <div class="glass-card p-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Name</th><th>Owner</th><th>Status</th><th>Listeners</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($rooms as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td><a href="{{ url('rooms/' . $r['id']) }}">{{ $r['name'] }}</a></td>
                <td>{{ $r['owner_username'] }}</td>
                <td><span class="badge bg-{{ $r['status'] === 'active' ? 'success' : 'secondary' }}">{{ $r['status'] }}</span></td>
                <td>{{ $r['current_listeners'] }}</td>
                <td>
                    <form method="POST" action="{{ url('admin/rooms/' . $r['id'] . '/feature') }}" class="d-inline">@csrf<button class="btn btn-sm btn-warning">Feature</button></form>
                    <form method="POST" action="{{ url('admin/rooms/' . $r['id'] . '/lock') }}" class="d-inline">@csrf<button class="btn btn-sm btn-ghost">Lock</button></form>
                    <form method="POST" action="{{ url('admin/rooms/' . $r['id'] . '/delete') }}" class="d-inline" onsubmit="return confirm('Ban this room?')">@csrf<button class="btn btn-sm btn-outline-danger">Ban</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
