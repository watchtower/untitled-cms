@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Tag</h1>

        <form action="{{ route('admin.tags.update', $tag) }}" method="POST" class="mt-6">
            @csrf
            @method('PUT')
            @include('admin.tags._form')

            <div class="mt-6 flex items-center justify-end gap-x-6">
                <a href="{{ route('admin.tags.index') }}" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
