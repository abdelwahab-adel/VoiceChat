@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:640px;">
    <div class="glass-card p-4">
        <h2><i class="bi bi-buildings me-2 text-primary"></i> Create Agency</h2>
        <p class="text-muted">Build your own creator community</p>
        <form method="POST" action="{{ url('agencies/create') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" maxlength="120" required>
                <label for="name">Agency name</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="description" name="description" style="height:120px" maxlength="2000"></textarea>
                <label for="description">Description</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="country" name="country" maxlength="80">
                <label for="country">Country (optional)</label>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted">Logo (optional)</label>
                <input type="file" class="form-control" name="logo" accept="image/*">
            </div>
            <button class="btn btn-primary-gradient w-100">Create Agency</button>
        </form>
    </div>
</div>
@endsection
