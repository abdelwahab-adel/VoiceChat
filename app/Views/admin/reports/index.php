@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2><i class="bi bi-flag me-2"></i> Reports</h2>
    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><a class="nav-link {{ $status === 'pending' ? 'active' : '' }}" href="?status=pending">Pending</a></li>
        <li class="nav-item"><a class="nav-link {{ $status === 'resolved' ? 'active' : '' }}" href="?status=resolved">Resolved</a></li>
        <li class="nav-item"><a class="nav-link {{ $status === 'dismissed' ? 'active' : '' }}" href="?status=dismissed">Dismissed</a></li>
    </ul>
    <div class="glass-card p-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Type</th><th>Target</th><th>Reporter</th><th>Reason</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($reports as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td><span class="badge bg-warning">{{ $r['target_type'] }}</span></td>
                <td>#{{ $r['target_id'] }}</td>
                <td>{{ $r['reporter_display_name'] ?? $r['reporter_username'] }}</td>
                <td>{{ $r['reason'] }}</td>
                <td><small>{{ time_ago($r['created_at']) }}</small></td>
                <td>
                    @if($r['status'] === 'pending')
                    <form method="POST" action="{{ url('admin/reports/' . $r['id'] . '/resolve') }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Resolve</button></form>
                    <form method="POST" action="{{ url('admin/reports/' . $r['id'] . '/dismiss') }}" class="d-inline">@csrf<button class="btn btn-sm btn-secondary">Dismiss</button></form>
                    @else
                    <span class="badge bg-light text-dark">{{ $r['status'] }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
