<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminUserCommand extends Command
{
    protected $signature = 'app:ensure-admin-user
                            {--email=admin@example.com : Admin login email}
                            {--password= : Plain password (prompted if omitted)}
                            {--name=Admin User : Display name}';

    protected $description = 'Create or reset the primary admin login (for production recovery)';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) ($this->option('password') ?: $this->secret('Admin password (min 8 chars)'));

        if (strlen($password) < 8) {
            $this->components->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = (string) $this->option('name');
        $user->password = Hash::make($password);
        $user->is_admin = true;
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
        }
        $user->save();

        $this->components->info("Admin ready: {$email}");
        $this->line('Log in at /login with that email and the password you just set.');

        return self::SUCCESS;
    }
}
