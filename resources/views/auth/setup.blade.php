<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 normal-case">
        {{ __('Welcome! This is the one-time setup for your MLB Draft app. Create your admin login below — no server commands required.') }}
    </div>

    <form method="POST" action="{{ route('setup.store') }}" class="normal-case">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Your name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
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
                    @checked(old('load_players', true))
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
</x-guest-layout>
