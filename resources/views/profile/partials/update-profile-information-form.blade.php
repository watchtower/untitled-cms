<section>
    <header class="mb-8">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900">
                {{ __('Profile Information') }}
            </h2>
        </div>

        <p class="text-sm text-gray-600">
            {{ __("Keep your account information up to date to maintain your L33t status.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-8">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name Field -->
            <div class="space-y-2">
                <x-input-label for="name" :value="__('Display Name')" class="text-sm font-semibold text-gray-700" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <x-text-input id="name" name="name" type="text" 
                        class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 hover:border-gray-400 transition-colors duration-200" 
                        :value="old('name', $user->name)" required autofocus autocomplete="name" 
                        placeholder="Enter your display name" />
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <!-- Email Field -->
            <div class="space-y-2">
                <x-input-label for="email" :value="__('Email Address')" class="text-sm font-semibold text-gray-700" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <x-text-input id="email" name="email" type="email" 
                        class="pl-10 block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 hover:border-gray-400 transition-colors duration-200" 
                        :value="old('email', $user->email)" required autocomplete="username" 
                        placeholder="Enter your email address" />
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <!-- Email Verification Warning -->
        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-amber-800">
                            {{ __('Email verification required') }}
                        </p>
                        <p class="text-sm text-amber-700 mt-1">
                            {{ __('Your email address is unverified. Please verify your email to maintain full access to L33t features.') }}
                        </p>
                        <button form="send-verification" 
                            class="mt-3 inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Resend verification email') }}
                        </button>
                    </div>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-md">
                        <p class="text-sm font-medium text-green-800 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Verification email sent! Check your inbox.') }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <div class="flex items-center gap-4">
                <button type="submit" 
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-widest hover:from-purple-700 hover:to-blue-700 focus:from-purple-700 focus:to-blue-700 active:from-purple-900 active:to-blue-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all ease-in-out duration-200 shadow-lg hover:shadow-xl">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Update Profile') }}
                </button>

                @if (session('status') === 'profile-updated')
                    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                        class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Profile updated successfully!') }}
                    </div>
                @endif
            </div>

            <!-- Account Status -->
            <div class="text-right">
                <div class="text-xs text-gray-500 mb-1">Account Status</div>
                <div class="flex items-center gap-2">
                    @if($user->isActive())
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-sm font-medium text-green-700">Active</span>
                    @else
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-sm font-medium text-red-700">Inactive</span>
                    @endif
                </div>
            </div>
        </div>
    </form>
</section>
