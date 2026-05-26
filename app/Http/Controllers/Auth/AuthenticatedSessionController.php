<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ApplicationDatabaseBackupTrigger;
use App\Support\ApplicationDatabaseBootstrap;
use App\Support\ApplicationInstallationMarker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login', [
            'needsFirstRunSetup' => ApplicationDatabaseBootstrap::needsFirstRunSetup(),
            'laravelCloudSqliteMisconfiguration' => ApplicationDatabaseBootstrap::laravelCloudSqliteMisconfiguration(),
            'recoverableBackup' => ApplicationDatabaseBootstrap::latestRecoverableBackupSummary(),
            'installationPreviouslyCompleted' => ApplicationInstallationMarker::exists(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        ApplicationDatabaseBackupTrigger::maybeRun();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
