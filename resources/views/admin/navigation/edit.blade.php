@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Navigation Item</h1>
            <a href="{{ route('admin.navigation.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Back to Navigation
            </a>
        </div>

        <form method="POST" action="{{ route('admin.navigation.update', $navigationItem) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700 mb-2">Label</label>
                        <input 
                            type="text" 
                            id="label" 
                            name="label" 
                            value="{{ old('label', $navigationItem->label) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                            autofocus
                            placeholder="Menu item text"
                        >
                        <p class="mt-1 text-xs text-gray-500">The text that will be displayed in the navigation menu</p>
                        @error('label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select 
                            id="type" 
                            name="type" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            required
                        >
                            <option value="">Select navigation type</option>
                            <option value="page" {{ old('type', $navigationItem->type) === 'page' ? 'selected' : '' }}>Link to Page</option>
                            <option value="url" {{ old('type', $navigationItem->type) === 'url' ? 'selected' : '' }}>External URL</option>
                            <option value="custom" {{ old('type', $navigationItem->type) === 'custom' ? 'selected' : '' }}>Custom URL</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="page-select" class="hidden">
                        <label for="page_id" class="block text-sm font-medium text-gray-700 mb-2">Select Page</label>
                        <select 
                            id="page_id" 
                            name="page_id" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option value="">Choose a page</option>
                            @foreach($pages as $page)
                                <option value="{{ $page->id }}" {{ old('page_id', $navigationItem->page_id) == $page->id ? 'selected' : '' }}>
                                    {{ $page->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('page_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="url-input" class="hidden">
                        <label for="url" class="block text-sm font-medium text-gray-700 mb-2">URL</label>
                        <input 
                            type="url" 
                            id="url" 
                            name="url" 
                            value="{{ old('url', $navigationItem->url) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="https://example.com or /custom-path"
                        >
                        <p class="mt-1 text-xs text-gray-500">Enter the full URL including http:// or https:// for external links</p>
                        @error('url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="css_class" class="block text-sm font-medium text-gray-700 mb-2">CSS Classes</label>
                        <input 
                            type="text" 
                            id="css_class" 
                            name="css_class" 
                            value="{{ old('css_class', $navigationItem->css_class) }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            placeholder="custom-class another-class"
                        >
                        <p class="mt-1 text-xs text-gray-500">Optional CSS classes to apply to this navigation item</p>
                        @error('css_class')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Settings -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Settings</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">Parent Item</label>
                                <select 
                                    id="parent_id" 
                                    name="parent_id" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                    <option value="">Top Level Item</option>
                                    @foreach($parentItems as $item)
                                        @if($item->id !== $navigationItem->id)
                                            <option value="{{ $item->id }}" {{ old('parent_id', $navigationItem->parent_id) == $item->id ? 'selected' : '' }}>
                                                {{ $item->label }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Leave empty for top-level menu item</p>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_visible" 
                                    name="is_visible" 
                                    value="1"
                                    {{ old('is_visible', $navigationItem->is_visible) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                <label for="is_visible" class="ml-2 block text-sm text-gray-900">
                                    Visible in navigation
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="opens_new_tab" 
                                    name="opens_new_tab" 
                                    value="1"
                                    {{ old('opens_new_tab', $navigationItem->opens_new_tab) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                >
                                <label for="opens_new_tab" class="ml-2 block text-sm text-gray-900">
                                    Open in new tab
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col space-y-3">
                        <button 
                            type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200"
                        >
                            Update Navigation Item
                        </button>
                        <a 
                            href="{{ route('admin.navigation.index') }}" 
                            class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded text-center transition-colors duration-200"
                        >
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const pageSelect = document.getElementById('page-select');
    const urlInput = document.getElementById('url-input');
    const urlField = document.getElementById('url');
    const pageField = document.getElementById('page_id');

    function toggleFields() {
        const selectedType = typeSelect.value;
        
        // Hide all conditional fields first
        pageSelect.classList.add('hidden');
        urlInput.classList.add('hidden');
        
        // Clear field requirements
        urlField.removeAttribute('required');
        pageField.removeAttribute('required');
        
        // Show relevant field based on type
        if (selectedType === 'page') {
            pageSelect.classList.remove('hidden');
            pageField.setAttribute('required', 'required');
        } else if (selectedType === 'url' || selectedType === 'custom') {
            urlInput.classList.remove('hidden');
            urlField.setAttribute('required', 'required');
            
            // Update placeholder and label based on type
            const label = urlInput.querySelector('label');
            const helpText = urlInput.querySelector('p');
            
            if (selectedType === 'url') {
                label.textContent = 'External URL';
                urlField.placeholder = 'https://example.com';
                helpText.textContent = 'Enter the full URL including http:// or https://';
                urlField.type = 'url';
            } else {
                label.textContent = 'Custom Path';
                urlField.placeholder = '/custom-path';
                helpText.textContent = 'Enter a custom path starting with /';
                urlField.type = 'text';
            }
        }
    }

    // Initial toggle on page load
    toggleFields();
    
    // Toggle fields when type changes
    typeSelect.addEventListener('change', toggleFields);
});
</script>
@endsection