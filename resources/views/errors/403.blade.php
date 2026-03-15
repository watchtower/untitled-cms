@extends('errors.layout', ['title' => '403 Forbidden'])

@section('content')
    <div class="code">403</div>
    <h1>Access denied</h1>
    <p>{{ ($exception && $exception->getMessage()) ? $exception->getMessage() : "You don't have permission to access this page." }}</p>
    <div class="actions">
        <a href="{{ url('/') }}" class="btn-primary">Go home</a>
        <a href="javascript:history.back()" class="btn-ghost">Go back</a>
    </div>
@endsection
