@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Site Settings</h1>
        </div>

        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
            @csrf
            @method('PUT')

            @foreach ($settings as $group => $groupSettings)
                <div class="bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 capitalize">{{ ucfirst($group) }} Settings</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach ($groupSettings as $setting)
                            <div>
                                <label for="settings_{{ $setting->key }}" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ ucwords(str_replace('_', ' ', $setting->key)) }}
                                </label>
                                
                                @if ($setting->description)
                                    <p class="text-xs text-gray-500 mb-2">{{ $setting->description }}</p>
                                @endif

                                @if ($setting->type === 'boolean')
                                    <div class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            id="settings_{{ $setting->key }}" 
                                            name="settings[{{ $setting->key }}]" 
                                            value="1"
                                            {{ $setting->value ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        >
                                        <label for="settings_{{ $setting->key }}" class="ml-2 text-sm text-gray-600">
                                            Enable
                                        </label>
                                    </div>
                                @elseif ($setting->type === 'json')
                                    <textarea 
                                        id="settings_{{ $setting->key }}" 
                                        name="settings[{{ $setting->key }}]" 
                                        rows="3"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        placeholder="Enter JSON array"
                                    >{{ is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value }}</textarea>
                                @else
                                    <input 
                                        type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" 
                                        id="settings_{{ $setting->key }}" 
                                        name="settings[{{ $setting->key }}]" 
                                        value="{{ $setting->value }}"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button 
                    type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                >
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection