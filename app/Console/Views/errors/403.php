@extends('layouts.app')
@section('content')
<div class="container py-5 text-center">
    <div class="error-page">
        <h1 class="display-1 gradient-text">403</h1>
        <p class="lead">You don't have permission to access this.</p>
        <a href="{{ url('') }}" class="btn btn-primary-gradient">Back to home</a>
    </div>
</div>
@endsection
