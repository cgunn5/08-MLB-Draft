<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureUserCommand extends Command
{
    protected $signature = 'app:ensure-user
                            {--email= : Login email}
                            {--password= : Plain password (prompted if omitted)}
                            {--name= : Display name (defaults from email)}
                            {--admin : Grant admin / edit access}';

    protected $description = 'Create or reset a user login (view-only by default)';

    public function handle(): int
    {
        $email = trim((string) $this->option('email'));
        if ($email === '') {
            $this->components->error('Email required. Pass --email=.');

            return self::FAILURE;
        }

        $password = (string) $this->option('password');
        if ($password === '') {
            if ($this->input->isInteractive()) {
                $password = (string) $this->secret('Password (min 8 chars)');
            } else {
                $this->components->error('Password required. Pass --password= or run interactively.');

                return self::FAILURE;
            }
        }

        if (strlen($password) < 8) {
            $this->components->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        if ($name === '') {
            $name = (string) str($email)->before('@')->replace(['.', '_', '-'], ' ')->title();
        }

        $isAdmin = (bool) $this->option('admin');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->is_admin = $isAdmin;
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
        $user->save();

        $role = $isAdmin ? 'admin' : 'view-only';
        $this->components->info("User ready ({$role}): {$email}");
        $this->line('Log in at /login with that email and the password you just set.');

        return self::SUCCESS;
    }
}
