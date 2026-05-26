<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($laravelCloudSqliteMisconfiguration ?? false)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-900 normal-case">
            <p class="font-semibold">{{ __('Database not configured for Laravel Cloud') }}</p>
            <p class="mt-2">{{ __('Log in will not work until a hosted database is attached. See setup instructions:') }}
                <a class="underline font-medium" href="{{ route('setup') }}">{{ __('Fix hosting') }}</a>
            </p>
        </div>
    @elseif ($needsFirstRunSetup ?? false)
        @if ($recoverableBackup)
            <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-900 normal-case">
                <p class="font-semibold">{{ __('Previous data found') }}</p>
                <p class="mt-2">{{ __('Restore your account instead of creating a new one:') }}
                    <a class="underline font-medium" href="{{ route('setup') }}">{{ __('Restore from backup') }}</a>
                </p>
            </div>
        @elseif ($installationPreviouslyCompleted ?? false)
            <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900 normal-case">
                <p>{{ __('This app was set up before but no login accounts were found. Try your existing email and password below first.') }}</p>
            </div>
        @else
            <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900 normal-case">
                <p>{{ __('First time here?') }}
                    <a class="underline font-medium" href="{{ route('setup') }}">{{ __('Create your admin account') }}</a>
                </p>
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
