@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:640px;">
    <h2>Edit Gift</h2>
    <form method="POST" action="{{ url('admin/gifts/' . $gift['id'] . '/update') }}" enctype="multipart/form-data" class="glass-card p-4">
        @csrf
        <div class="form-floating mb-3"><input type="text" class="form-control" name="name" value="{{ $gift['name'] }}" required maxlength="80"><label>Name</label></div>
        <div class="form-floating mb-3"><input type="text" class="form-control" name="description" value="{{ $gift['description'] }}" maxlength="255"><label>Description</label></div>
        <div class="row g-2 mb-3">
            <div class="col"><input type="number" class="form-control" name="price_coins" value="{{ $gift['price_coins'] }}" min="1" required></div>
            <div class="col">
                <select class="form-select" name="rarity">
                    @foreach(['common','rare','epic','legendary','mythic'] as $r)<option value="{{ $r }}" @if($gift['rarity'] === $r) selected @endif>{{ $r }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_animated" value="1" id="anim" @if($gift['is_animated']) checked @endif>
            <label class="form-check-label" for="anim">Animated</label>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @if($gift['is_active']) checked @endif>
            <label class="form-check-label" for="act">Active</label>
        </div>
        <div class="mb-3"><label class="form-label small">Image (optional)</label><input type="file" class="form-control" name="image" accept="image/*">
        @if($gift['image'])<img src="{{ url('public/' . $gift['image']) }}" class="mt-2 rounded" width="60">@endif
        </div>
        <button class="btn btn-primary-gradient">Save</button>
    </form>
</div>
@endsection
