@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Taxonomy Management</h1>
                <p class="text-sm text-gray-600 mt-1">Manage categories and tags for organizing content</p>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H3a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-900">Categories</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $categoryStats['total'] }}</p>
                        <p class="text-xs text-blue-700">{{ $categoryStats['used'] }} used, {{ $categoryStats['unused'] }} unused</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">Tags</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $tagStats['total'] }}</p>
                        <p class="text-xs text-gray-700">{{ $tagStats['used'] }} used, {{ $tagStats['unused'] }} unused</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('admin.taxonomy.index', ['tab' => 'categories']) }}" 
                   class="{{ $activeTab === 'categories' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Categories
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activeTab === 'categories' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $categories->count() }}
                    </span>
                </a>
                <a href="{{ route('admin.taxonomy.index', ['tab' => 'tags']) }}" 
                   class="{{ $activeTab === 'tags' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Tags
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $activeTab === 'tags' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $tags->count() }}
                    </span>
                </a>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            @if($activeTab === 'categories')
                @include('admin.taxonomy.partials.categories', ['items' => $categories, 'type' => 'category'])
            @else
                @include('admin.taxonomy.partials.tags', ['items' => $tags, 'type' => 'tag'])
            @endif
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div id="bulk-actions-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Bulk Actions
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Choose an action to perform on selected items.
                            </p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <button type="button" onclick="performBulkAction('delete')" 
                                    class="w-full inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-red-50 text-base font-medium text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                                Delete Selected
                            </button>
                            <button type="button" onclick="performBulkAction('convert')" 
                                    class="w-full inline-flex justify-center rounded-md border border-indigo-300 shadow-sm px-4 py-2 bg-indigo-50 text-base font-medium text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                                Convert to {{ $activeTab === 'categories' ? 'Tags' : 'Categories' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeBulkModal()" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedItems = [];
const currentType = '{{ $activeTab === "categories" ? "category" : "tag" }}';

function toggleItemSelection(id) {
    const index = selectedItems.indexOf(id);
    if (index > -1) {
        selectedItems.splice(index, 1);
    } else {
        selectedItems.push(id);
    }
    updateBulkActionsVisibility();
}

function selectAllItems() {
    const checkboxes = document.querySelectorAll('input[name="item_ids[]"]');
    const selectAllCheckbox = document.getElementById('select-all');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
        const id = parseInt(checkbox.value);
        if (selectAllCheckbox.checked && !selectedItems.includes(id)) {
            selectedItems.push(id);
        } else if (!selectAllCheckbox.checked) {
            const index = selectedItems.indexOf(id);
            if (index > -1) selectedItems.splice(index, 1);
        }
    });
    
    updateBulkActionsVisibility();
}

function updateBulkActionsVisibility() {
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');
    
    if (selectedItems.length > 0) {
        bulkActions.classList.remove('hidden');
        selectedCount.textContent = selectedItems.length;
    } else {
        bulkActions.classList.add('hidden');
    }
}

function openBulkModal() {
    if (selectedItems.length === 0) {
        alert('Please select items first.');
        return;
    }
    document.getElementById('bulk-actions-modal').classList.remove('hidden');
}

function closeBulkModal() {
    document.getElementById('bulk-actions-modal').classList.add('hidden');
}

function performBulkAction(action) {
    if (selectedItems.length === 0) {
        alert('Please select items first.');
        return;
    }

    if (action === 'delete') {
        if (!confirm(`Are you sure you want to delete ${selectedItems.length} selected items? This action cannot be undone.`)) {
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.taxonomy.bulk-delete") }}';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Add type
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = currentType;
        form.appendChild(typeInput);
        
        // Add IDs
        selectedItems.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    } else if (action === 'convert') {
        if (!confirm(`Are you sure you want to convert ${selectedItems.length} selected ${currentType === 'category' ? 'categories' : 'tags'} to ${currentType === 'category' ? 'tags' : 'categories'}?`)) {
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.taxonomy.convert") }}';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Add conversion types
        const fromTypeInput = document.createElement('input');
        fromTypeInput.type = 'hidden';
        fromTypeInput.name = 'from_type';
        fromTypeInput.value = currentType;
        form.appendChild(fromTypeInput);
        
        const toTypeInput = document.createElement('input');
        toTypeInput.type = 'hidden';
        toTypeInput.name = 'to_type';
        toTypeInput.value = currentType === 'category' ? 'tag' : 'category';
        form.appendChild(toTypeInput);
        
        // Add IDs
        selectedItems.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
    
    closeBulkModal();
}

// Close modal when clicking outside
document.getElementById('bulk-actions-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkModal();
    }
});
</script>
@endsection