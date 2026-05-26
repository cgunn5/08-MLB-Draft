<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetupAdminRequest;
use App\Models\User;
use App\Support\ApplicationDatabaseBackupTrigger;
use App\Support\ApplicationDatabaseBootstrap;
use Database\Seeders\AggregatesPlayerSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
            return redirect()->route('login');
        }

        return view('auth.setup', [
            'recoverableBackup' => ApplicationDatabaseBootstrap::latestRecoverableBackupSummary(),
        ]);
    }

    public function restoreFromBackup(): RedirectResponse
    {
        if (! ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
            return redirect()->route('login');
        }

        try {
            $backup = ApplicationDatabaseBootstrap::restoreFromLatestRecoverableBackup();
        } catch (\Throwable $e) {
            return back()->withErrors(['restore' => $e->getMessage()]);
        }

        if ($backup === null) {
            return back()->withErrors([
                'restore' => __('No backup with saved user accounts was found.'),
            ]);
        }

        if (ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
            return back()->withErrors([
                'restore' => __('Backup was copied but no user accounts were found inside it.'),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', __('Your previous database was restored from backup. Log in with your existing email and password.'));
    }

    public function store(SetupAdminRequest $request): RedirectResponse
    {
        if (! ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
            return redirect()->route('login');
        }

        ApplicationDatabaseBootstrap::ensureReady();

        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        if ($request->boolean('load_players')) {
            (new AggregatesPlayerSeeder)->run();
        }

        ApplicationDatabaseBackupTrigger::maybeRun();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', __('Your admin account is ready.'));
    }
}
