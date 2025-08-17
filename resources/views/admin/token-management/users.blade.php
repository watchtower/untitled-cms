@extends('admin.layout')

@section('content')
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">User Token Balances</h1>
                <p class="text-sm text-gray-600 mt-1">Manage individual user L33t Bytes balances</p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ route('admin.token-management.index') }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Overview
                </a>
                <a href="{{ route('admin.token-management.users') }}" 
                   class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    User Balances
                </a>
                <a href="{{ route('admin.token-management.transactions') }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    Transaction Log
                </a>
            </nav>
        </div>

        <!-- Search and Filters -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <form method="GET" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700">Search Users</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Name or email..." 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="min-w-0 flex-1">
                    <label for="subscription" class="block text-sm font-medium text-gray-700">Subscription Level</label>
                    <select name="subscription" id="subscription" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Levels</option>
                        @foreach($subscriptionLevels as $level)
                            <option value="{{ $level->id }}" {{ request('subscription') == $level->id ? 'selected' : '' }}>
                                {{ $level->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Filter
                    </button>
                    <a href="{{ route('admin.token-management.users') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Operations -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-medium text-blue-900 mb-3">Bulk Operations</h3>
            <form method="POST" action="{{ route('admin.token-management.bulk-operation') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="bulk_operation" class="block text-sm font-medium text-blue-700">Operation</label>
                        <select name="operation" id="bulk_operation" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Operation</option>
                            <option value="grant_to_all">Grant to All Users</option>
                            <option value="grant_to_subscription">Grant to Subscription Level</option>
                            <option value="reset_balances">Reset All Balances</option>
                        </select>
                    </div>
                    <div>
                        <label for="bulk_token_id" class="block text-sm font-medium text-blue-700">Token Type</label>
                        <select name="token_id" id="bulk_token_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Token</option>
                            @foreach($tokens as $token)
                                <option value="{{ $token->id }}">{{ $token->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="bulk_amount_field">
                        <label for="bulk_amount" class="block text-sm font-medium text-blue-700">Amount</label>
                        <input type="number" name="amount" id="bulk_amount" min="0" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div id="bulk_subscription_field" style="display: none;">
                        <label for="bulk_subscription_level_id" class="block text-sm font-medium text-blue-700">Subscription Level</label>
                        <select name="subscription_level_id" id="bulk_subscription_level_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Select Level</option>
                            @foreach($subscriptionLevels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="bulk_reason" class="block text-sm font-medium text-blue-700">Reason</label>
                        <input type="text" name="reason" id="bulk_reason" required placeholder="Reason for operation"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>
                <button type="submit" onclick="return confirm('Are you sure? This will affect multiple users.')"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Execute Bulk Operation
                </button>
            </form>
        </div>

        <!-- Users Table -->
        @if($users->count() > 0)
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token Balances</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                                                <span class="text-sm font-medium text-white">{{ substr($user->name, 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->subscriptionLevel)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                     @if($user->subscriptionLevel->level === 1) bg-gray-100 text-gray-800
                                                     @elseif($user->subscriptionLevel->level === 2) bg-blue-100 text-blue-800
                                                     @else bg-purple-100 text-purple-800 @endif">
                                            {{ $user->subscriptionLevel->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">No subscription</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @forelse($user->userTokens as $userToken)
                                            <div class="flex items-center text-sm">
                                                <span class="mr-2" style="color: {{ $userToken->token->color }}">{{ $userToken->token->icon }}</span>
                                                <span class="font-medium">{{ number_format($userToken->balance) }}</span>
                                                <span class="ml-1 text-gray-500">{{ $userToken->token->name }}</span>
                                            </div>
                                        @empty
                                            <span class="text-gray-400 text-sm">No tokens</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="openTokenModal('{{ $user->id }}', '{{ $user->name }}')" 
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        Manage Tokens
                                    </button>
                                    <a href="{{ route('admin.token-management.transactions', ['user_id' => $user->id]) }}" 
                                       class="text-gray-600 hover:text-gray-900">
                                        View History
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No users found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search filters.</p>
            </div>
        @endif
    </div>
</div>

<!-- Token Management Modal -->
<div id="tokenModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <form id="tokenForm" method="POST">
                @csrf
                <div>
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Manage Tokens for <span id="user-name"></span>
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="modal_token_id" class="block text-sm font-medium text-gray-700">Token Type</label>
                                <select name="token_id" id="modal_token_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Token</option>
                                    @foreach($tokens as $token)
                                        <option value="{{ $token->id }}">{{ $token->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="modal_action" class="block text-sm font-medium text-gray-700">Action</label>
                                <select name="action" id="modal_action" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select Action</option>
                                    <option value="add">Add Tokens</option>
                                    <option value="deduct">Deduct Tokens</option>
                                    <option value="set">Set Balance</option>
                                </select>
                            </div>
                            <div>
                                <label for="modal_amount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <input type="number" name="amount" id="modal_amount" min="0" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="modal_reason" class="block text-sm font-medium text-gray-700">Reason</label>
                                <input type="text" name="reason" id="modal_reason" required placeholder="Reason for this transaction"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update Balance
                    </button>
                    <button type="button" onclick="closeTokenModal()" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Bulk operation form logic
    document.getElementById('bulk_operation').addEventListener('change', function() {
        const operation = this.value;
        const amountField = document.getElementById('bulk_amount_field');
        const subscriptionField = document.getElementById('bulk_subscription_field');
        
        if (operation === 'grant_to_subscription') {
            subscriptionField.style.display = 'block';
            amountField.style.display = 'block';
        } else if (operation === 'grant_to_all') {
            subscriptionField.style.display = 'none';
            amountField.style.display = 'block';
        } else if (operation === 'reset_balances') {
            subscriptionField.style.display = 'none';
            amountField.style.display = 'none';
        } else {
            subscriptionField.style.display = 'none';
            amountField.style.display = 'none';
        }
    });

    // Token management modal
    function openTokenModal(userId, userName) {
        document.getElementById('user-name').textContent = userName;
        document.getElementById('tokenForm').action = `/admin/token-management/users/${userId}/update-balance`;
        document.getElementById('tokenModal').classList.remove('hidden');
    }

    function closeTokenModal() {
        document.getElementById('tokenModal').classList.add('hidden');
        document.getElementById('tokenForm').reset();
    }

    // Close modal when clicking outside
    document.getElementById('tokenModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeTokenModal();
        }
    });
</script>
@endsection