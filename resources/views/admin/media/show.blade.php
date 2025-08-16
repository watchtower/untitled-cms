<div class="flex justify-between items-center mb-6">
    <h3 class="text-lg font-bold text-gray-900">{{ $media->original_filename }}</h3>
    <button onclick="closeMediaModal()" class="text-gray-400 hover:text-gray-600">
        ✕
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Media Preview -->
    <div>
        @if($media->isImage())
            <img 
                src="{{ $media->url }}" 
                alt="{{ $media->alt_text }}"
                class="w-full rounded-lg shadow-sm"
            >
        @else
            <div class="bg-gray-100 rounded-lg p-12 text-center">
                <div class="text-6xl text-gray-400 mb-4">📄</div>
                <div class="text-lg font-medium text-gray-900">{{ $media->original_filename }}</div>
                <div class="text-sm text-gray-500">{{ $media->type }} • {{ $media->human_readable_size }}</div>
                <a 
                    href="{{ $media->url }}" 
                    target="_blank" 
                    class="inline-block mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                >
                    Download
                </a>
            </div>
        @endif
    </div>

    <!-- Media Details -->
    <div class="space-y-6">
        <!-- File Info -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">File Information</h4>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Filename:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->original_filename }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Type:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->mime_type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Size:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->human_readable_size }}</dd>
                </div>
                @if($media->metadata && isset($media->metadata['width']))
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Dimensions:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->metadata['width'] }} × {{ $media->metadata['height'] }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Uploaded:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->created_at->format('M j, Y g:i A') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Uploaded by:</dt>
                    <dd class="text-sm text-gray-900">{{ $media->uploader->name }}</dd>
                </div>
            </dl>
        </div>

        <!-- Edit Form -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">Edit Details</h4>
            <form id="media-edit-form" data-media-id="{{ $media->id }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="edit_alt_text" class="block text-sm font-medium text-gray-700 mb-1">Alt Text</label>
                    <input 
                        type="text" 
                        id="edit_alt_text" 
                        name="alt_text" 
                        value="{{ $media->alt_text }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        placeholder="Describe the file for accessibility"
                    >
                </div>
                
                <div>
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea 
                        id="edit_description" 
                        name="description" 
                        rows="3"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        placeholder="Optional description..."
                    >{{ $media->description }}</textarea>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"
                >
                    Update Details
                </button>
            </form>
        </div>

        <!-- URL Copy -->
        <div>
            <h4 class="font-medium text-gray-900 mb-3">File URL</h4>
            <div class="flex">
                <input 
                    type="text" 
                    value="{{ $media->url }}" 
                    readonly
                    class="flex-1 rounded-l-md border-gray-300 bg-gray-50 text-sm"
                >
                <button 
                    onclick="copyToClipboard('{{ $media->url }}')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-r-md text-sm"
                >
                    Copy
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex space-x-3">
            <a 
                href="{{ $media->url }}" 
                target="_blank" 
                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center"
            >
                View Full Size
            </a>
            <form method="POST" action="{{ route('admin.media.destroy', $media) }}" class="flex-1" 
                  onsubmit="return confirm('Are you sure you want to delete this file? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button 
                    type="submit" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                >
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('media-edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const mediaId = this.dataset.mediaId;
    
    fetch(`/admin/media/${mediaId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message or refresh content
            alert('Media details updated successfully!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the media details.');
    });
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Could show a temporary notification here
        alert('URL copied to clipboard!');
    });
}
</script>