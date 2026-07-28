@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:640px;">
    <h2>New Announcement</h2>
    <form method="POST" action="{{ url('admin/announcements/create') }}" class="glass-card p-4">
        @csrf
        <div class="form-floating mb-3"><input type="text" class="form-control" name="title" required maxlength="200"><label>Title</label></div>
        <div class="form-floating mb-3"><textarea class="form-control" name="body" style="height:120px" required></textarea><label>Body</label></div>
        <div class="row g-2 mb-3">
            <div class="col"><select class="form-select" name="type">@foreach(['info','warning','success','promo'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
            <div class="col"><select class="form-select" name="target">@foreach(['all','users','vip','agency'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" checked>
            <label class="form-check-label" for="act">Active</label>
        </div>
        <button class="btn btn-primary-gradient">Create</button>
    </form>
</div>
@endsection
