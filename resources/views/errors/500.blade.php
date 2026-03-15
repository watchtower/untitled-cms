@extends('errors.layout', ['title' => '500 Server Error'])

@section('content')
    <div class="code">500</div>
    <h1>Something went wrong</h1>
    <p>An unexpected error occurred. Please try again later. If the problem persists, contact the site administrator.</p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn-primary">Go home</a>
    </div>
@endsection
