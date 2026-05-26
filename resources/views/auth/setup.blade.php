<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 normal-case">
        {{ __('Welcome! Create your admin account once, then use login every time after that.') }}
    </div>

    @if ($installationPreviouslyCompleted && ! $recoverableBackup)
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-900 normal-case">
            <p class="font-semibold">{{ __('This app was set up before, but the database file is missing.') }}</p>
            <p class="mt-2">{{ __('Do not create a new account — that starts empty. Redeploy with the latest code and FORGE.md settings, or upload a SYNC bundle after you can log in.') }}</p>
        </div>
    @endif

    @if ($recoverableBackup)
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 p-4 text-sm text-green-900 normal-case space-y-3">
            <p class="font-semibold">{{ __('Previous data found — restore instead of creating a new account') }}</p>
            <p>
                {{ __('Automatic backup from :date. Restore keeps uploads, notes, grades, and your login.', [
                    'date' => isset($recoverableBackup['modified_at'])
                        ? \Illuminate\Support\Carbon::parse($recoverableBackup['modified_at'])->timezone(config('app.timezone'))->format('M j, Y g:i A')
                        : __('your last session'),
                ]) }}
            </p>
            <form method="POST" action="{{ route('setup.restore-backup') }}">
                @csrf
                <x-primary-button class="normal-case">
                    {{ __('Restore my data & go to login') }}
                </x-primary-button>
            </form>
        </div>
    @elseif (! $installationPreviouslyCompleted)
        <div class="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900 normal-case">
            {{ __('First time only: after this, you will always use the login page. Data is stored on the server under storage/app/ and survives redeploys when Forge is configured correctly (see FORGE.md).') }}
        </div>
    @endif

    @if ($errors->has('restore'))
        <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-800 normal-case">
            {{ $errors->first('restore') }}
        </div>
    @endif

    @if (! $installationPreviouslyCompleted || ! $recoverableBackup)
        <details class="mb-4 normal-case" @if (! $recoverableBackup && ! $installationPreviouslyCompleted) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-gray-800">
                {{ ($recoverableBackup || $installationPreviouslyCompleted) ? __('Create a new empty account (not recommended)') : __('Create your admin account') }}
            </summary>

            <form method="POST" action="{{ route('setup.store') }}" class="normal-case mt-4">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Your name')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_confirmation" :value="__('Confirm password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="mt-4 block">
                    <label for="load_players" class="inline-flex items-center">
                        <input
                            id="load_players"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            name="load_players"
                            value="1"
                            @checked(old('load_players', ! $recoverableBackup))
                        />
                        <span class="ms-2 text-sm text-gray-600">{{ __('Load bundled aggregate player list') }}</span>
                    </label>
                </div>

                <div class="mt-6 flex items-center justify-end">
                    <x-primary-button>
                        {{ __('Create account & continue') }}
                    </x-primary-button>
                </div>
            </form>
        </details>
    @endif
</x-guest-layout>
