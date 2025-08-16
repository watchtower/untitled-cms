@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
                <p class="text-sm text-gray-600 mt-1">Manage and organize your media files</p>
            </div>
            <button 
                onclick="document.getElementById('upload-modal').classList.remove('hidden')" 
                class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200"
            >
                <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" />
                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                </svg>
                Upload File
            </button>
        </div>


        <!-- Filters -->
        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search by filename, alt text, or description..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                </div>
                <div>
                    <select 
                        name="type"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Types</option>
                        <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Images</option>
                        <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Videos</option>
                        <option value="audio" {{ request('type') === 'audio' ? 'selected' : '' }}>Audio</option>
                        <option value="application" {{ request('type') === 'application' ? 'selected' : '' }}>Documents</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition-colors duration-200">
                        Filter
                    </button>
                    @if(request()->hasAny(['search', 'type']))
                        <a href="{{ route('admin.media.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded transition-colors duration-200">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

    <!-- Media Grid -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($media->count())
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6">
                @foreach($media as $item)
                    <div class="relative group cursor-pointer" onclick="showMediaModal({{ $item->id }})">
                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                            @if($item->isImage())
                                <img 
                                    src="{{ $item->url }}" 
                                    alt="{{ $item->alt_text }}"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <div class="text-center">
                                        <div class="text-2xl mb-2">📄</div>
                                        <div class="text-xs">{{ $item->type }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Overlay with info -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center">
                            <div class="text-white text-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <div class="text-sm font-medium">{{ Str::limit($item->original_filename, 20) }}</div>
                                <div class="text-xs">{{ $item->human_readable_size }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t">
                {{ $media->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No media files found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request()->hasAny(['search', 'type']))
                        No media files match your current filters.
                    @else
                        Get started by uploading your first file.
                    @endif
                </p>
                <div class="mt-6">
                    <button 
                        onclick="document.getElementById('upload-modal').classList.remove('hidden')" 
                        class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors duration-200"
                    >
                        <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" />
                            <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                        </svg>
                        Upload File
                    </button>
                </div>
            </div>
        @endif
    </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Upload File</h3>
            <button onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
        
        <form id="upload-form" method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">File</label>
                <input 
                    type="file" 
                    id="file" 
                    name="file" 
                    required
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                >
                <p class="mt-1 text-xs text-gray-500">Max size: 10MB</p>
            </div>
            
            <div>
                <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
                <input 
                    type="text" 
                    id="alt_text" 
                    name="alt_text" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Describe the file for accessibility"
                >
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    placeholder="Optional description..."
                ></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    onclick="document.getElementById('upload-modal').classList.add('hidden')"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                >
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Media Detail Modal -->
<div id="media-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border max-w-4xl shadow-lg rounded-md bg-white">
        <div id="media-modal-content">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function showMediaModal(mediaId) {
    fetch(`/admin/media/${mediaId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('media-modal-content').innerHTML = html;
            document.getElementById('media-modal').classList.remove('hidden');
        });
}

function closeMediaModal() {
    document.getElementById('media-modal').classList.add('hidden');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    const uploadModal = document.getElementById('upload-modal');
    const mediaModal = document.getElementById('media-modal');
    
    if (e.target === uploadModal) {
        uploadModal.classList.add('hidden');
    }
    if (e.target === mediaModal) {
        mediaModal.classList.add('hidden');
    }
});
</script>
@endsection