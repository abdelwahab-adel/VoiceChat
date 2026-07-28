@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:640px;">
    <div class="glass-card p-4">
        <h2>Edit Agency</h2>
        <form method="POST" action="{{ url('agencies/' . $agency['id'] . '/update') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" value="{{ $agency['name'] }}" maxlength="120" required>
                <label for="name">Agency name</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="description" name="description" style="height:120px">{{ $agency['description'] }}</textarea>
                <label for="description">Description</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="country" name="country" value="{{ $agency['country'] }}">
                <label for="country">Country</label>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary-gradient">Save</button>
                <a href="{{ url('agencies/' . $agency['slug']) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
