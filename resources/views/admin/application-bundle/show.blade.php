<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('SYNC DATA') }}
            </h2>
            <a href="{{ url('/admin/restore-data/download') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('DOWNLOAD BUNDLE') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'bundle-imported')
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ __('Live data was replaced from your bundle.') }}
                    @if (session('bundle_manifest.upload_count') !== null)
                        <span class="block mt-1 text-green-700">
                            {{ __('Stat files restored:') }} {{ session('bundle_manifest.upload_count') }}
                        </span>
                    @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc ps-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <p class="text-sm text-gray-600">
                        {{ __('Move everything from your Mac to the live app in one step: player notes, grades, working board, users, upload settings, and the CSV stat files themselves.') }}
                    </p>

                    <ol class="list-decimal ps-5 text-sm text-gray-700 space-y-2">
                        <li>{{ __('On your Mac (localhost), log in as an admin and open this page.') }}</li>
                        <li>{{ __('Click Download bundle and save the zip file.') }}</li>
                        <li>{{ __('On the live site, log in as an admin and open Sync data (same page).') }}</li>
                        <li>{{ __('Upload the zip below and confirm.') }}</li>
                        <li>{{ __('Log in again with the email and password from your Mac account (the live admin account is replaced).') }}</li>
                    </ol>

                    <p class="text-sm text-gray-500">
                        {{ __('A backup of the live database is saved automatically before import under storage/app/database-backups/.') }}
                    </p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold text-sm uppercase tracking-widest text-gray-800 mb-4">
                        {{ __('Restore on this server') }}
                    </h3>

                    <form method="POST" action="{{ route('admin.application-bundle.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="bundle" :value="__('Bundle zip file')" />
                            <input
                                id="bundle"
                                name="bundle"
                                type="file"
                                accept=".zip,application/zip"
                                required
                                class="mt-1 block w-full text-sm text-gray-600 file:me-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700"
                            />
                        </div>

                        <label class="flex items-start gap-3 text-sm text-gray-700">
                            <input
                                type="checkbox"
                                name="confirm"
                                value="1"
                                required
                                class="mt-1 rounded border-gray-300 text-gray-800 shadow-sm focus:ring-gray-500"
                            />
                            <span>{{ __('Replace all data on this server with the bundle (notes, grades, users, stat files). This cannot be undone except from the automatic pre-import backup.') }}</span>
                        </label>

                        <x-primary-button>
                            {{ __('Import bundle') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
