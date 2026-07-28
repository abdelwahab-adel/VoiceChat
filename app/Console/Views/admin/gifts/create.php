@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:640px;">
    <h2>New Gift</h2>
    <form method="POST" action="{{ url('admin/gifts/create') }}" enctype="multipart/form-data" class="glass-card p-4">
        @csrf
        <div class="form-floating mb-3"><input type="text" class="form-control" name="name" required maxlength="80"><label>Name</label></div>
        <div class="form-floating mb-3"><input type="text" class="form-control" name="description" maxlength="255"><label>Description</label></div>
        <div class="row g-2 mb-3">
            <div class="col"><input type="number" class="form-control" name="price_coins" placeholder="Price in coins" min="1" required></div>
            <div class="col">
                <select class="form-select" name="rarity">
                    @foreach(['common','rare','epic','legendary','mythic'] as $r)<option>{{ $r }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col"><input type="text" class="form-control" name="category" placeholder="Category"></div>
            <div class="col"><input type="number" class="form-control" name="sort_order" value="0" placeholder="Order"></div>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_animated" value="1" id="anim">
            <label class="form-check-label" for="anim">Animated</label>
        </div>
        <div class="mb-3"><label class="form-label small">Image (optional)</label><input type="file" class="form-control" name="image" accept="image/*"></div>
        <button class="btn btn-primary-gradient">Create</button>
    </form>
</div>
@endsection
