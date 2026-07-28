@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2>Login History</h2>
    <div class="glass-card p-3">
        <table class="table table-sm">
            <thead><tr><th>Time</th><th>User</th><th>Email</th><th>Status</th><th>IP</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
            <tr>
                <td><small>{{ $r['created_at'] }}</small></td>
                <td>{{ $r['username'] ?? '—' }}</td>
                <td><small>{{ $r['email'] }}</small></td>
                <td><span class="badge bg-{{ $r['status'] === 'success' ? 'success' : 'danger' }}">{{ $r['status'] }}</span></td>
                <td><small class="text-muted">{{ $r['ip'] }}</small></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
