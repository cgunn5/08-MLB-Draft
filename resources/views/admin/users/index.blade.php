<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('USER ACCOUNTS') }}
            </h2>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('INVITE USER') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'user-created')
                <p class="text-sm text-gray-600">{{ __('NEW ACCOUNT CREATED.') }}</p>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-2 pe-6">{{ __('NAME') }}</th>
                                <th class="py-2 pe-6">{{ __('EMAIL') }}</th>
                                <th class="py-2">{{ __('ADMIN') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 pe-6">{{ $user->name }}</td>
                                    <td class="py-2 pe-6">{{ $user->email }}</td>
                                    <td class="py-2">{{ $user->is_admin ? __('YES') : __('NO') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
