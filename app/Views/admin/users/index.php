@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <h2><i class="bi bi-people me-2"></i> Users</h2>
    <form method="GET" class="row g-2 my-3">
        <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Search username/email/id" value="{{ $q }}"></div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach(['active','suspended','banned','pending','deleted'] as $s)
                    <option value="{{ $s }}" @if($status === $s) selected @endif>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-ghost w-100">Filter</button></div>
    </form>

    <div class="glass-card p-3">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Level</th><th>Coins</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u['id'] }}</td>
                    <td><a href="{{ url('admin/users/' . $u['id']) }}">{{ $u['display_name'] ?? $u['username'] }}</a></td>
                    <td><small>{{ $u['email'] }}</small></td>
                    <td><span class="badge bg-{{ $u['role'] === 'admin' ? 'danger' : 'secondary' }}">{{ $u['role'] }}</span></td>
                    <td><span class="badge bg-{{ $u['status'] === 'active' ? 'success' : 'warning' }}">{{ $u['status'] }}</span></td>
                    <td>{{ $u['level'] }}</td>
                    <td>{{ number_format($u['coins']) }}</td>
                    <td><small>{{ time_ago($u['created_at']) }}</small></td>
                    <td><a href="{{ url('admin/users/' . $u['id']) }}" class="btn btn-sm btn-ghost">Manage</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if(($pager['last_page'] ?? 1) > 1)
        <nav><ul class="pagination justify-content-center">
            <li class="page-item @if($pager['page'] <= 1) disabled @endif"><a class="page-link" href="?page={{ $pager['page']-1 }}">Prev</a></li>
            <li class="page-item active"><span class="page-link">{{ $pager['page'] }} / {{ $pager['last_page'] }}</span></li>
            <li class="page-item @if($pager['page'] >= $pager['last_page']) disabled @endif"><a class="page-link" href="?page={{ $pager['page']+1 }}">Next</a></li>
        </ul></nav>
        @endif
    </div>
</div>
@endsection
