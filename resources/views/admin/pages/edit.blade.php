@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Page: {{ $page->title }}</h1>
            <a href="{{ route('admin.pages.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                Back to Pages
            </a>
        </div>

        <form method="POST" action="{{ route('admin.pages.update', $page) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="{{ old('title', $page->title) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                            autofocus
                        >
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                        <input 
                            type="text" 
                            id="slug" 
                            name="slug" 
                            value="{{ old('slug', $page->slug) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="Leave empty to auto-generate from title"
                        >
                        <p class="mt-1 text-xs text-gray-500">URL-friendly version of the title. Leave empty to auto-generate.</p>
                        @error('slug')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-medium text-gray-700 mb-2">Summary</label>
                        <textarea 
                            id="summary" 
                            name="summary" 
                            rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="Brief description of the page..."
                        >{{ old('summary', $page->summary) }}</textarea>
                        @error('summary')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                        <textarea 
                            id="content" 
                            name="content" 
                            rows="15"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >{{ old('content', $page->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Publishing -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Publishing</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select 
                                    id="status" 
                                    name="status" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Published</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="published_at" class="block text-sm font-medium text-gray-700 mb-2">Publish Date</label>
                                <input 
                                    type="datetime-local" 
                                    id="published_at" 
                                    name="published_at" 
                                    value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                <p class="mt-1 text-xs text-gray-500">Leave empty to publish immediately when status is published.</p>
                                @error('published_at')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Categories & Tags -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Categories & Tags</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="categories" class="block text-sm font-medium text-gray-700 mb-2">Categories</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                                    @forelse($categories as $category)
                                        <label class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                name="categories[]" 
                                                value="{{ $category->id }}"
                                                {{ in_array($category->id, old('categories', $page->categories->pluck('id')->toArray())) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">{{ $category->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-500">No categories available. <a href="{{ route('admin.categories.create') }}" class="text-indigo-600 hover:text-indigo-800">Create one</a>.</p>
                                    @endforelse
                                </div>
                                @error('categories')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                                <input 
                                    type="text" 
                                    id="tags" 
                                    name="tags" 
                                    value="{{ old('tags') }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    placeholder="Type tags and press Enter..."
                                    autocomplete="off"
                                >
                                <p class="mt-1 text-xs text-gray-500">Type tag names separated by commas or press Enter after each tag</p>
                                
                                <!-- Selected tags display -->
                                <div id="selected-tags" class="mt-2 flex flex-wrap gap-1"></div>
                                
                                <!-- Hidden input to store selected tag IDs -->
                                <input type="hidden" id="selected-tag-ids" name="tag_ids" value="{{ old('tag_ids', json_encode($page->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name, 'isNew' => false]))) }}">
                                
                                <!-- Tag suggestions dropdown -->
                                <div id="tag-suggestions" class="absolute z-10 mt-1 bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-40 overflow-y-auto"></div>
                                
                                @error('tags')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('tag_ids')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                <input 
                                    type="text" 
                                    id="meta_title" 
                                    name="meta_title" 
                                    value="{{ old('meta_title', $page->meta_title) }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    placeholder="Leave empty to use page title"
                                >
                                @error('meta_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                <textarea 
                                    id="meta_description" 
                                    name="meta_description" 
                                    rows="3"
                                    maxlength="320"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    placeholder="Brief description for search engines..."
                                >{{ old('meta_description', $page->meta_description) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Recommended: 150-160 characters</p>
                                @error('meta_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                                <input 
                                    type="text" 
                                    id="meta_keywords" 
                                    name="meta_keywords" 
                                    value="{{ old('meta_keywords') ? (is_array(old('meta_keywords')) ? implode(', ', old('meta_keywords')) : old('meta_keywords')) : (is_array($page->meta_keywords) ? implode(', ', $page->meta_keywords) : '') }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    placeholder="keyword1, keyword2, keyword3"
                                >
                                <p class="mt-1 text-xs text-gray-500">Separate with commas</p>
                                @error('meta_keywords')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col space-y-3">
                        <button 
                            type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        >
                            Update Page
                        </button>
                        <a 
                            href="{{ route('admin.pages.show', $page) }}" 
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-center"
                        >
                            View Page
                        </a>
                        <a 
                            href="{{ route('admin.pages.index') }}" 
                            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded text-center"
                        >
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://ckeditor4-19-1.cdn.3b.my/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CKEditor
    CKEDITOR.replace('content', {
        height: 400,
        toolbar: [
            { name: 'document', items: ['Source', 'Preview'] },
            { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
            { name: 'editing', items: ['Find', 'Replace', 'SelectAll'] },
            '/',
            { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
            { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv'] },
            { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
            { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
            '/',
            { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
            { name: 'colors', items: ['TextColor', 'BGColor'] },
            { name: 'tools', items: ['Maximize', 'ShowBlocks'] }
        ],
        removeButtons: '',
        format_tags: 'p;h1;h2;h3;h4;h5;h6;pre',
        allowedContent: true,
        extraAllowedContent: 'div(*);span(*);p(*);h1(*);h2(*);h3(*);h4(*);h5(*);h6(*)',
        filebrowserBrowseUrl: '/filemanager?type=Files',
        filebrowserImageBrowseUrl: '/filemanager?type=Images',
        filebrowserUploadUrl: '/filemanager/upload?type=Files&_token=',
        filebrowserImageUploadUrl: '/filemanager/upload?type=Images&_token='
    });

    // Auto-generate slug from title (only if slug is empty)
    document.getElementById('title').addEventListener('input', function() {
        const title = this.value;
        const slugField = document.getElementById('slug');
        
        if (!slugField.value || !slugField.dataset.manuallyEdited) {
            const slug = title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            
            slugField.value = slug;
        }
    });

    // Track manual slug editing
    document.getElementById('slug').addEventListener('input', function() {
        this.dataset.manuallyEdited = 'true';
    });

    // Tag functionality
    initTagInput();
});

function initTagInput() {
    const tagInput = document.getElementById('tags');
    const tagSuggestions = document.getElementById('tag-suggestions');
    const selectedTagsContainer = document.getElementById('selected-tags');
    const selectedTagIdsInput = document.getElementById('selected-tag-ids');
    
    let availableTags = @json($tags ?? []);
    let selectedTags = [];
    
    // Load existing tags
    if (selectedTagIdsInput.value) {
        try {
            const existingTags = JSON.parse(selectedTagIdsInput.value);
            selectedTags = existingTags || [];
            updateSelectedTagsDisplay();
        } catch (e) {
            console.error('Error parsing existing tags:', e);
        }
    }

    tagInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        
        if (query.length === 0) {
            hideSuggestions();
            return;
        }

        // Filter available tags
        const filteredTags = availableTags.filter(tag => 
            tag.name.toLowerCase().includes(query) && 
            !selectedTags.some(selected => selected.id === tag.id)
        );

        showSuggestions(filteredTags, query);
    });

    tagInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addNewTag(this.value.trim());
        }
    });

    function showSuggestions(tags, query) {
        if (tags.length === 0 && query) {
            tagSuggestions.innerHTML = `<div class="p-2 text-sm text-gray-600">Press Enter to create "${query}"</div>`;
        } else {
            tagSuggestions.innerHTML = tags.map(tag => 
                `<div class="p-2 hover:bg-gray-100 cursor-pointer text-sm" onclick="selectTag(${tag.id}, '${tag.name}')">${tag.name}</div>`
            ).join('');
        }
        
        tagSuggestions.classList.remove('hidden');
    }

    function hideSuggestions() {
        tagSuggestions.classList.add('hidden');
    }

    function addNewTag(name) {
        if (!name) return;
        
        // Check if tag already exists
        const existingTag = availableTags.find(tag => tag.name.toLowerCase() === name.toLowerCase());
        
        if (existingTag && !selectedTags.some(selected => selected.id === existingTag.id)) {
            selectedTags.push({ id: existingTag.id, name: existingTag.name, isNew: false });
        } else if (!existingTag) {
            // Create new tag object with temporary ID
            const newTag = { id: 'new_' + Date.now(), name: name, isNew: true };
            selectedTags.push(newTag);
        }
        
        updateSelectedTagsDisplay();
        tagInput.value = '';
        hideSuggestions();
    }

    window.selectTag = function(id, name) {
        const tag = availableTags.find(t => t.id === id);
        if (tag && !selectedTags.some(selected => selected.id === tag.id)) {
            selectedTags.push({ id: tag.id, name: tag.name, isNew: false });
            updateSelectedTagsDisplay();
            tagInput.value = '';
            hideSuggestions();
        }
    };

    function removeTag(tagId) {
        selectedTags = selectedTags.filter(tag => tag.id !== tagId);
        updateSelectedTagsDisplay();
    }

    function updateSelectedTagsDisplay() {
        selectedTagsContainer.innerHTML = selectedTags.map(tag => 
            `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                ${tag.name}
                <button type="button" onclick="removeTag('${tag.id}')" class="ml-1 text-indigo-600 hover:text-indigo-800">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>`
        ).join('');
        
        // Update hidden input with tag data
        selectedTagIdsInput.value = JSON.stringify(selectedTags.map(tag => ({
            id: tag.isNew ? null : tag.id,
            name: tag.name,
            isNew: tag.isNew || false
        })));
    }

    window.removeTag = removeTag;

    // Click outside to hide suggestions
    document.addEventListener('click', function(e) {
        if (!tagInput.contains(e.target) && !tagSuggestions.contains(e.target)) {
            hideSuggestions();
        }
    });
}
</script>
@endsection