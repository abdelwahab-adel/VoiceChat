@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2>Activity Log</h2>
    <div class="glass-card p-3">
        <table class="table table-sm">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Subject</th><th>IP</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
            <tr>
                <td><small>{{ $r['created_at'] }}</small></td>
                <td>{{ $r['username'] ?? '—' }}</td>
                <td><span class="badge bg-secondary">{{ $r['action'] }}</span></td>
                <td><small>{{ $r['subject_type'] ?? '' }} {{ $r['subject_id'] ?? '' }}</small></td>
                <td><small class="text-muted">{{ $r['ip'] }}</small></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
