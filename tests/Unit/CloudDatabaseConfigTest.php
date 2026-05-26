<?php

namespace Tests\Unit;

use App\Support\CloudDatabaseConfig;
use App\Support\HostedEnvironment;
use Tests\TestCase;

class CloudDatabaseConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('DB_CONNECTION');
        putenv('DB_HOST');
        unset($_ENV['DB_CONNECTION'], $_ENV['DB_HOST'], $_SERVER['DB_CONNECTION'], $_SERVER['DB_HOST']);

        parent::tearDown();
    }

    public function test_applies_mysql_when_laravel_cloud_injects_database_host(): void
    {
        config(['app.url' => 'https://draft-app-main-eiz4fa.laravel.cloud']);
        config(['database.default' => 'sqlite']);

        $_ENV['DB_HOST'] = 'db-a1dfe3c4.us-east-2.db.laravel.cloud';
        $_ENV['DB_CONNECTION'] = 'mysql';

        CloudDatabaseConfig::apply();

        $this->assertSame('mysql', config('database.default'));
        $this->assertFalse(HostedEnvironment::laravelCloudSqliteMisconfiguration());
    }

    public function test_detects_sqlite_misconfiguration_when_no_host_is_injected(): void
    {
        config(['app.url' => 'https://draft-app-main-eiz4fa.laravel.cloud']);
        config(['database.default' => 'sqlite']);

        $this->assertTrue(HostedEnvironment::laravelCloudSqliteMisconfiguration());
    }
}
