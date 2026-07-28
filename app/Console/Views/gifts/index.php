@extends('layouts.app')
@section('content')
<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-gift-fill me-2 text-warning"></i> Send a Gift</h2>
            <p class="text-muted">Show appreciation to your favorite hosts</p>
        </div>
        <div class="coin-pill"><i class="bi bi-coin"></i> {{ number_format($user['coins'] ?? 0) }}</div>
    </div>

    <div class="row g-3" id="giftCatalog">
        @foreach($gifts as $gift)
        <div class="col-4 col-md-3 col-lg-2">
            <div class="gift-card rarity-{{ $gift['rarity'] }}" data-gift-id="{{ $gift['id'] }}" data-price="{{ $gift['price_coins'] }}">
                <div class="gift-image">
                    @if($gift['image'])
                        <img src="{{ url('public/' . $gift['image']) }}" alt="{{ $gift['name'] }}">
                    @else
                        <div class="gift-placeholder">🎁</div>
                    @endif
                </div>
                <h6 class="gift-name">{{ $gift['name'] }}</h6>
                <div class="gift-price"><i class="bi bi-coin"></i> {{ number_format($gift['price_coins']) }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
