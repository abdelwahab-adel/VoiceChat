@extends('layouts.app')
@section('content')
<div class="container py-5 text-center">
    <div class="error-page">
        <h1 class="display-1 gradient-text">404</h1>
        <p class="lead">The page you're looking for doesn't exist.</p>
        <a href="{{ url('') }}" class="btn btn-primary-gradient">Back to home</a>
    </div>
</div>
@endsection
