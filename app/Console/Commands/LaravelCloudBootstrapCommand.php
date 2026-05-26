<?php

namespace App\Console\Commands;

use App\Support\ApplicationDatabaseBootstrap;
use App\Support\HostedEnvironment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class LaravelCloudBootstrapCommand extends Command
{
    protected $signature = 'app:laravel-cloud-bootstrap';

    protected $description = 'Migrate and seed the admin account on Laravel Cloud (run as the deploy command)';

    public function handle(): int
    {
        if (! HostedEnvironment::isLaravelCloud()) {
            $this->components->warn('Not running on Laravel Cloud — running migrate only.');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(trim(Artisan::output()));

            return self::SUCCESS;
        }

        if (HostedEnvironment::laravelCloudSqliteMisconfiguration()) {
            $this->components->error('SQLite does not persist on Laravel Cloud.');
            $this->line('Attach Laravel MySQL or Serverless Postgres under Resources, remove DB_CONNECTION=sqlite from environment variables, then redeploy.');
            $this->line('See LARAVEL_CLOUD.md in the repository.');

            return self::FAILURE;
        }

        $this->components->info('Running migrations…');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(trim(Artisan::output()));

        ApplicationDatabaseBootstrap::resetBootstrappedForTesting();

        if (! ApplicationDatabaseBootstrap::needsFirstRunSetup()) {
            $this->components->info('Database ready — at least one user account exists.');

            return self::SUCCESS;
        }

        $email = (string) env('ADMIN_EMAIL', '');
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->components->warn('No users yet. Set ADMIN_EMAIL and ADMIN_PASSWORD environment variables, then redeploy.');
            $this->line('Or complete one-time setup in the browser after migrations succeed.');

            return self::SUCCESS;
        }

        Artisan::call('app:ensure-admin-user', [
            '--email' => $email,
            '--password' => $password,
            '--name' => (string) env('ADMIN_NAME', 'Admin'),
            '--no-interaction' => true,
        ]);

        $this->line(trim(Artisan::output()));

        return self::SUCCESS;
    }
}
