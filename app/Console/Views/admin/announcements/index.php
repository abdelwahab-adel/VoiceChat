@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <div class="d-flex justify-content-between mb-3">
        <h2><i class="bi bi-megaphone me-2"></i> Announcements</h2>
        <a href="{{ url('admin/announcements/create') }}" class="btn btn-primary-gradient"><i class="bi bi-plus-lg"></i> New</a>
    </div>
    <div class="glass-card p-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Active</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($items as $a)
            <tr>
                <td>{{ $a['id'] }}</td>
                <td>{{ $a['title'] }}</td>
                <td><span class="badge bg-{{ $a['type'] === 'warning' ? 'warning' : ($a['type'] === 'success' ? 'success' : 'info') }}">{{ $a['type'] }}</span></td>
                <td>@if($a['is_active'])<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-x-circle text-danger"></i>@endif</td>
                <td><small>{{ time_ago($a['created_at']) }}</small></td>
                <td>
                    <form method="POST" action="{{ url('admin/announcements/' . $a['id'] . '/toggle') }}" class="d-inline">@csrf<button class="btn btn-sm btn-ghost">Toggle</button></form>
                    <form method="POST" action="{{ url('admin/announcements/' . $a['id'] . '/delete') }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
