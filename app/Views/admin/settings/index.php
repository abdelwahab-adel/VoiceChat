@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:760px;">
    <h2><i class="bi bi-gear me-2"></i> Settings</h2>
    <form method="POST" action="{{ url('admin/settings/update') }}" class="glass-card p-4">
        @csrf
        @foreach($settings as $s)
        <div class="mb-3">
            <label class="form-label small text-muted">
                {{ $s['key_name'] }}
                <span class="badge bg-secondary">{{ $s['group_name'] }}</span>
            </label>
            <input type="text" class="form-control" name="setting_{{ $s['key_name'] }}" value="{{ $s['value'] }}">
        </div>
        @endforeach
        <button class="btn btn-primary-gradient">Save all</button>
    </form>
</div>
@endsection
