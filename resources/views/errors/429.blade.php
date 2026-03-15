@extends('errors.layout', ['title' => '429 Too Many Requests'])

@section('content')
    <div class="code">429</div>
    <h1>Too many requests</h1>
    <p>You're sending requests too quickly. Please wait a moment and try again.</p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn-primary">Go home</a>
    </div>
@endsection
