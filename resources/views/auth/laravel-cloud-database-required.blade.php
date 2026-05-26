<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 normal-case">
        {{ __('Your app is on Laravel Cloud. SQLite cannot save data here — the server resets its disk on every deploy.') }}
    </div>

    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-900 normal-case space-y-3">
        <p class="font-semibold">{{ __('Do not create another admin account on this screen.') }}</p>
        <p>{{ __('That form only writes to a temporary file that disappears on the next deploy. Your login and notes must live in a hosted database instead.') }}</p>
    </div>

    <div class="mb-4 rounded-md bg-amber-50 border border-amber-200 p-4 text-sm text-amber-950 normal-case space-y-3">
        <p class="font-semibold">{{ __('Fix (one time, in the Laravel Cloud dashboard)') }}</p>
        <ol class="list-decimal list-inside space-y-2">
            <li>{{ __('Open') }} <a class="underline font-medium" href="https://cloud.laravel.com" target="_blank" rel="noopener">cloud.laravel.com</a> → {{ __('your app → this environment') }}</li>
            <li>{{ __('Resources → Add database → Laravel MySQL (or Serverless Postgres)') }}</li>
            <li>{{ __('Environment variables: delete DB_CONNECTION=sqlite if it is listed') }}</li>
            <li>{{ __('Add: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NAME (your login — used once on deploy)') }}</li>
            <li>{{ __('Deploy command:') }} <code class="text-xs bg-white px-1 py-0.5 rounded">php artisan app:laravel-cloud-bootstrap</code></li>
            <li>{{ __('Redeploy, then open') }} <a class="underline" href="{{ route('login') }}">/login</a></li>
        </ol>
    </div>

    <p class="text-sm text-gray-600 normal-case">
        {{ __('Full steps:') }} <code class="text-xs">LARAVEL_CLOUD.md</code> {{ __('in the GitHub repo.') }}
    </p>
</x-guest-layout>
