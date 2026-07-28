@extends('layouts.app')
@section('content')
<div class="container my-4" style="max-width:720px;">
    <div class="glass-card p-4">
        <h2 class="mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i> Create a Room</h2>
        <p class="text-muted">Set up a voice room and invite people to talk</p>

        <form method="POST" action="{{ url('rooms/create') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" maxlength="80" required>
                <label for="name">Room name</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="description" name="description" style="height:100px" maxlength="500"></textarea>
                <label for="description">Description</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Type</label>
                    <select class="form-select" name="type" id="typeSelect">
                        <option value="public">🌍 Public</option>
                        <option value="password">🔒 Password</option>
                        <option value="private">👁️ Private</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Category</label>
                    <select class="form-select" name="category">
                        @foreach(['general','music','gaming','education','talk','dating','entertainment'] as $c)
                            <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Mic seats</label>
                    <select class="form-select" name="max_seats">
                        <option value="8" selected>8 seats</option>
                        <option value="16">16 seats</option>
                    </select>
                </div>
            </div>

            <div class="form-floating mb-3 d-none" id="passwordWrap">
                <input type="text" class="form-control" id="password" name="password" placeholder="Password">
                <label for="password">Room password</label>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="language" placeholder="Language (e.g. en)">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="country" placeholder="Country (optional)">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-muted">Cover image (optional)</label>
                <input type="file" class="form-control" name="cover" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary-gradient w-100 btn-lg">Create Room</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('typeSelect').addEventListener('change', e => {
    document.getElementById('passwordWrap').classList.toggle('d-none', e.target.value !== 'password');
});
</script>
@endsection
