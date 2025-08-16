@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $page->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">View page details and content</p>
            </div>
            <div class="flex space-x-2">
                @can('update', $page)
                    <a href="{{ route('admin.pages.edit', $page) }}" 
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        Edit Page
                    </a>
                @endcan
                <a href="{{ route('admin.pages.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                    Back to Pages
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Page Content -->
            <div class="lg:col-span-2">
                <div class="bg-gray-50 overflow-hidden rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Page Content</h3>
                        
                        @if($page->summary)
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Summary</h4>
                                <div class="bg-white rounded-lg p-4 border">
                                    <p class="text-gray-900">{{ $page->summary }}</p>
                                </div>
                            </div>
                        @endif

                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Content</h4>
                            <div class="bg-white rounded-lg p-4 border prose max-w-none">
                                {!! $page->content ?: '<p class="text-gray-500 italic">No content available</p>' !!}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO Information -->
                @if($page->meta_title || $page->meta_description || $page->meta_keywords)
                    <div class="bg-gray-50 overflow-hidden rounded-lg mt-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">SEO Information</h3>
                            
                            <dl class="space-y-4">
                                @if($page->meta_title)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Meta Title</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $page->meta_title }}</dd>
                                    </div>
                                @endif

                                @if($page->meta_description)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Meta Description</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $page->meta_description }}</dd>
                                    </div>
                                @endif

                                @if($page->meta_keywords && count($page->meta_keywords))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Meta Keywords</dt>
                                        <dd class="mt-1">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($page->meta_keywords as $keyword)
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        {{ $keyword }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Page Details Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 overflow-hidden rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Page Details</h3>
                        
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $page->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($page->status) }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Slug</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono bg-white px-2 py-1 rounded border">
                                    {{ $page->slug }}
                                </dd>
                            </div>

                            @if($page->published_at)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Published Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $page->published_at->format('M j, Y g:i A') }}
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $page->created_at->format('M j, Y g:i A') }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ $page->updated_at->format('M j, Y g:i A') }}
                                </dd>
                            </div>

                            @if($page->creator)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $page->creator->name }}</dd>
                                </div>
                            @endif

                            @if($page->updater && $page->updater->id !== $page->creator?->id)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated By</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $page->updater->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gray-50 overflow-hidden rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                        
                        <div class="space-y-3">
                            @can('update', $page)
                                <a href="{{ route('admin.pages.edit', $page) }}" 
                                   class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-center block">
                                    Edit Page
                                </a>
                            @endcan

                            @can('create', \App\Models\Page::class)
                                <form method="POST" action="{{ route('admin.pages.duplicate', $page) }}" class="w-full">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Duplicate Page
                                    </button>
                                </form>
                            @endcan

                            <a href="/{{ $page->slug }}" 
                               target="_blank"
                               class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center block">
                                View Live Page
                            </a>

                            @can('delete', $page)
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" 
                                      onsubmit="return confirm('Are you sure you want to delete this page?')"
                                      class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                        Delete Page
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                </div>

                <!-- URL Preview -->
                <div class="bg-gray-50 overflow-hidden rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Page URL</h3>
                        
                        <div class="flex">
                            <input type="text" 
                                   value="{{ url('/' . $page->slug) }}" 
                                   readonly
                                   class="flex-1 rounded-l-md border-gray-300 bg-gray-50 text-sm">
                            <button onclick="copyToClipboard('{{ url('/' . $page->slug) }}')"
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-r-md text-sm">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('URL copied to clipboard!');
    });
}
</script>
@endsection