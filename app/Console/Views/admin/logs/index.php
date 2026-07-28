@extends('layouts.app')
@section('content')
<div class="container my-4">
    <h2><i class="bi bi-file-text me-2"></i> System Logs</h2>
    <div class="glass-card p-3">
        <table class="table">
            <thead><tr><th>File</th><th>Size</th><th>Modified</th><th></th></tr></thead>
            <tbody>
            @foreach($files as $f)
            <tr>
                <td>{{ $f['name'] }}</td>
                <td>{{ format_bytes($f['size']) }}</td>
                <td>{{ date('Y-m-d H:i', $f['mtime']) }}</td>
                <td><a href="{{ url('admin/logs/view?file=' . urlencode($f['name'])) }}" class="btn btn-sm btn-ghost">View</a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
