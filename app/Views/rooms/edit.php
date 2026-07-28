@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:720px;">
    <div class="glass-card p-4">
        <h2>Edit Room</h2>
        <form method="POST" action="{{ url('rooms/' . $room['id'] . '/update') }}">
            @csrf
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" value="{{ $room['name'] }}" maxlength="80" required>
                <label for="name">Room name</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="description" name="description" style="height:100px" maxlength="500">{{ $room['description'] }}</textarea>
                <label for="description">Description</label>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Category</label>
                    <select class="form-select" name="category">
                        @foreach(['general','music','gaming','education','talk'] as $c)
                            <option value="{{ $c }}" @if($room['category'] === $c) selected @endif>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-muted">Max seats</label>
                    <select class="form-select" name="max_seats">
                        <option value="8" @if($room['max_seats'] == 8) selected @endif>8</option>
                        <option value="16" @if($room['max_seats'] == 16) selected @endif>16</option>
                    </select>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary-gradient">Save</button>
                <a href="{{ url('rooms/' . $room['id']) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
