<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Subscription') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Subscription Details') }}</h3>
                    <div class="mt-4">
                        <p><strong>{{ __('Plan:') }}</strong> {{ $subscriptionName }}</p>
                        <p><strong>{{ __('Permanent Tokens (Bytes):') }}</strong> {{ $tokenBalance }}</p>
                        <p><strong>{{ __('Monthly Credits (Bits):') }}</strong> {{ $creditBalance }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
