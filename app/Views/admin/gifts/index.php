@extends('layouts.app')
@section('content')
<div class="container-fluid my-4">
    <div class="d-flex justify-content-between mb-3">
        <h2><i class="bi bi-gift me-2"></i> Gifts</h2>
        <a href="{{ url('admin/gifts/create') }}" class="btn btn-primary-gradient"><i class="bi bi-plus-lg"></i> New Gift</a>
    </div>
    <div class="glass-card p-3">
        <table class="table align-middle">
            <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Rarity</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($gifts as $g)
            <tr>
                <td>{{ $g['id'] }}</td>
                <td>{{ $g['name'] }}</td>
                <td><i class="bi bi-coin text-warning"></i> {{ number_format($g['price_coins']) }}</td>
                <td><span class="badge bg-{{ ['common'=>'secondary','rare'=>'info','epic'=>'primary','legendary'=>'warning','mythic'=>'danger'][$g['rarity']] ?? 'secondary' }}">{{ $g['rarity'] }}</span></td>
                <td>@if($g['is_active'])<i class="bi bi-check-circle text-success"></i>@else<i class="bi bi-x-circle text-danger"></i>@endif</td>
                <td>
                    <a href="{{ url('admin/gifts/' . $g['id'] . '/edit') }}" class="btn btn-sm btn-ghost">Edit</a>
                    <form method="POST" action="{{ url('admin/gifts/' . $g['id'] . '/delete') }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
