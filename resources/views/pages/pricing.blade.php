<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-6">Subscription Plans</h1>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach ($subscriptionLevels as $level)
                            <div class="border border-gray-200 rounded-lg p-6 flex flex-col">
                                <h2 class="text-xl font-bold">{{ $level->name }}</h2>
                                <p class="text-gray-500">{{ $level->description }}</p>

                                <div class="my-4">
                                    <span class="text-4xl font-bold">{{ $level->formatted_price }}</span>
                                </div>

                                <ul class="space-y-2 text-gray-600">
                                    <li>{{ $level->getMonthlyCreditAllocation() }} Monthly Credits (Bits)</li>
                                    <li>{{ $level->getPermanentTokenAllocation() }} Unlock Tokens (Bytes)</li>
                                    <li>{{ $level->features['site_monitor_retention_days'] }}-day Site Monitor Retention</li>
                                    <li>Lookup History: Last {{ $level->features['lookup_history_days'] }}</li>
                                </ul>

                                <div class="mt-auto pt-6">
                                    <a href="#" class="w-full text-center bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
                                        Choose Plan
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
