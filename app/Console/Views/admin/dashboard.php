@extends('layouts.app')
@section('head')
<style>.admin-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; }</style>
@endsection
@section('content')
<div class="container-fluid my-4 px-3">
    <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</h2>

    <div class="admin-grid mb-4">
        <div class="stat-card"><div class="stat-icon bg-primary"><i class="bi bi-people"></i></div><div><strong>{{ number_format($stats['users']) }}</strong><br><small class="text-muted">Users</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-success"><i class="bi bi-circle-fill"></i></div><div><strong>{{ number_format($stats['online']) }}</strong><br><small class="text-muted">Online</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-info"><i class="bi bi-mic"></i></div><div><strong>{{ number_format($stats['active_rooms']) }}</strong><br><small class="text-muted">Active Rooms</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-warning"><i class="bi bi-buildings"></i></div><div><strong>{{ number_format($stats['agencies']) }}</strong><br><small class="text-muted">Agencies</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-danger"><i class="bi bi-exclamation-triangle"></i></div><div><strong>{{ number_format($stats['reports']) }}</strong><br><small class="text-muted">Pending Reports</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-secondary"><i class="bi bi-shield-x"></i></div><div><strong>{{ number_format($stats['banned']) }}</strong><br><small class="text-muted">Banned</small></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:gold"><i class="bi bi-coin"></i></div><div><strong>{{ number_format($stats['gifts']) }}</strong><br><small class="text-muted">Gift coins</small></div></div>
        <div class="stat-card"><div class="stat-icon bg-dark"><i class="bi bi-chat"></i></div><div><strong>{{ number_format($stats['messages']) }}</strong><br><small class="text-muted">Messages</small></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="glass-card p-3">
                <h5>Latest Users</h5>
                <table class="table table-sm">
                    <thead><tr><th>User</th><th>Status</th><th>Joined</th></tr></thead>
                    <tbody>
                    @foreach($latestUsers as $u)
                        <tr>
                            <td><a href="{{ url('admin/users/' . $u['id']) }}">{{ $u['display_name'] ?? $u['username'] }}</a></td>
                            <td><span class="badge bg-{{ $u['status'] === 'active' ? 'success' : 'danger' }}">{{ $u['status'] }}</span></td>
                            <td><small class="text-muted">{{ time_ago($u['created_at']) }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass-card p-3">
                <h5>Latest Rooms</h5>
                <table class="table table-sm">
                    <thead><tr><th>Room</th><th>Owner</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($latestRooms as $r)
                        <tr>
                            <td>{{ $r['name'] }}</td>
                            <td>{{ $r['owner'] }}</td>
                            <td><span class="badge bg-{{ $r['status'] === 'active' ? 'success' : 'secondary' }}">{{ $r['status'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @if(!empty($latestReports))
        <div class="col-12">
            <div class="glass-card p-3">
                <h5>Pending Reports</h5>
                <table class="table table-sm">
                    <thead><tr><th>Type</th><th>Reporter</th><th>Reason</th><th>Date</th></tr></thead>
                    <tbody>
                    @foreach($latestReports as $r)
                        <tr>
                            <td><span class="badge bg-warning">{{ $r['target_type'] }}</span></td>
                            <td>{{ $r['reporter'] }}</td>
                            <td>{{ $r['reason'] }}</td>
                            <td><small class="text-muted">{{ time_ago($r['created_at']) }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
