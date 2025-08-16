{{-- Filter Bar Component
     Usage: @include('admin.partials.filter-bar', [
         'action' => route('admin.pages.index'),
         'fields' => [
             ['type' => 'search', 'name' => 'search', 'placeholder' => 'Search...', 'value' => request('search')],
             ['type' => 'select', 'name' => 'status', 'options' => ['published' => 'Published', 'draft' => 'Draft'], 'value' => request('status'), 'placeholder' => 'All Status'],
             ['type' => 'date', 'name' => 'created_after', 'label' => 'Created After', 'value' => request('created_after')]
         ],
         'clearRoute' => route('admin.pages.index')
     ])
--}}

<div class="mb-6 bg-gray-50 p-4 rounded-lg">
    <form method="GET" action="{{ $action }}" class="flex flex-wrap gap-4">
        @foreach($fields as $field)
            <div class="{{ $field['type'] === 'search' ? 'flex-1 min-w-64' : '' }}">
                @if($field['type'] === 'search')
                    <input 
                        type="text" 
                        name="{{ $field['name'] }}" 
                        value="{{ $field['value'] ?? '' }}"
                        placeholder="{{ $field['placeholder'] ?? 'Search...' }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                @elseif($field['type'] === 'select')
                    <select 
                        name="{{ $field['name'] }}"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">{{ $field['placeholder'] ?? 'All' }}</option>
                        @foreach($field['options'] as $value => $label)
                            <option value="{{ $value }}" {{ ($field['value'] ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                @elseif($field['type'] === 'date')
                    <div>
                        @if(isset($field['label']))
                            <label for="{{ $field['name'] }}" class="block text-xs font-medium text-gray-700 mb-1">
                                {{ $field['label'] }}
                            </label>
                        @endif
                        <input 
                            type="date" 
                            id="{{ $field['name'] }}"
                            name="{{ $field['name'] }}" 
                            value="{{ $field['value'] ?? '' }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                    </div>
                @elseif($field['type'] === 'number')
                    <div>
                        @if(isset($field['label']))
                            <label for="{{ $field['name'] }}" class="block text-xs font-medium text-gray-700 mb-1">
                                {{ $field['label'] }}
                            </label>
                        @endif
                        <input 
                            type="number" 
                            id="{{ $field['name'] }}"
                            name="{{ $field['name'] }}" 
                            value="{{ $field['value'] ?? '' }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            min="{{ $field['min'] ?? '' }}"
                            max="{{ $field['max'] ?? '' }}"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                    </div>
                @endif
            </div>
        @endforeach
        
        <div class="flex gap-2">
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded transition-colors duration-200">
                Filter
            </button>
            @if(isset($clearRoute) && request()->hasAny(collect($fields)->pluck('name')->toArray()))
                <a href="{{ $clearRoute }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded transition-colors duration-200">
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>