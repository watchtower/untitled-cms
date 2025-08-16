@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
            @if($page->summary)
                <p class="text-xl text-gray-600 leading-relaxed">{{ $page->summary }}</p>
            @endif
            <div class="flex items-center text-sm text-gray-500 mt-4">
                <time datetime="{{ $page->published_at?->toISOString() }}">
                    Published {{ $page->published_at?->format('F j, Y') }}
                </time>
                @if($page->updated_at->gt($page->created_at))
                    <span class="mx-2">•</span>
                    <time datetime="{{ $page->updated_at->toISOString() }}">
                        Updated {{ $page->updated_at->format('F j, Y') }}
                    </time>
                @endif
            </div>
        </div>

        <!-- Page Content -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-8 sm:px-8 sm:py-10">
                @if($page->content)
                    <div class="prose prose-lg max-w-none">
                        {!! $page->content !!}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">📄</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No content available</h3>
                        <p class="text-gray-500">This page doesn't have any content yet.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Page Meta Information (for development/debugging) -->
        @if(app()->environment('local') && auth()->check())
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Page Information (Development Mode)</h4>
                <div class="text-xs text-blue-700 space-y-1">
                    <div><strong>Slug:</strong> {{ $page->slug }}</div>
                    <div><strong>Status:</strong> {{ $page->status }}</div>
                    <div><strong>Created:</strong> {{ $page->created_at->format('M j, Y g:i A') }}</div>
                    @if($page->creator)
                        <div><strong>Author:</strong> {{ $page->creator->name }}</div>
                    @endif
                </div>
                @auth
                    <div class="mt-3">
                        <a href="{{ route('admin.pages.edit', $page) }}" 
                           class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                            Edit Page
                        </a>
                    </div>
                @endauth
            </div>
        @endif
    </div>
</div>
@endsection