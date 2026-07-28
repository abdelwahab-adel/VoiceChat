@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2><i class="bi bi-buildings me-2"></i> Agencies</h2>
    <form method="GET" class="row g-2 my-3">
        <div class="col-md-6"><input type="text" name="q" class="form-control" placeholder="Search by name" value="{{ $q }}"></div>
        <div class="col-md-2"><button class="btn btn-ghost w-100">Filter</button></div>
    </form>
    <div class="glass-card p-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Name</th><th>Owner</th><th>Members</th><th>Level</th><th>Verified</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($agencies as $a)
            <tr>
                <td>{{ $a['id'] }}</td>
                <td><a href="{{ url('agencies/' . $a['slug']) }}">{{ $a['name'] }}</a></td>
                <td>{{ $a['owner_username'] }}</td>
                <td>{{ $a['members_count'] }}</td>
                <td>Lvl {{ $a['level'] }}</td>
                <td>@if($a['verified'])<i class="bi bi-patch-check-fill text-info"></i>@else — @endif</td>
                <td><span class="badge bg-{{ $a['status'] === 'active' ? 'success' : 'danger' }}">{{ $a['status'] }}</span></td>
                <td>
                    <form method="POST" action="{{ url('admin/agencies/' . $a['id'] . '/verify') }}" class="d-inline">@csrf<button class="btn btn-sm btn-info">Verify</button></form>
                    <form method="POST" action="{{ url('admin/agencies/' . $a['id'] . '/delete') }}" class="d-inline" onsubmit="return confirm('Ban?')">@csrf<button class="btn btn-sm btn-outline-danger">Ban</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
