@extends('errors.layout', ['title' => '404 Not Found'])

@section('content')
    <div class="code">404</div>
    <h1>Page not found</h1>
    <p>The page you're looking for doesn't exist or has been moved.</p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn-primary">Go home</a>
        <a href="javascript:history.back()" class="btn-ghost">Go back</a>
    </div>
@endsection
