<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\ApplicationBundleSqliteTableCopier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApplicationBundleSqliteTableCopierTest extends TestCase
{
    private string $sourcePath;

    private string $targetPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourcePath = storage_path('app/testing-bundle-source-'.uniqid('', true).'.sqlite');
        $this->targetPath = storage_path('app/testing-bundle-target-'.uniqid('', true).'.sqlite');

        config(['database.connections.sqlite.database' => $this->sourcePath]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->admin()->create([
            'email' => 'mac-user@example.com',
            'name' => 'Mac User',
        ]);

        config(['database.connections.sqlite.database' => $this->targetPath]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        User::factory()->admin()->create(['email' => 'cloud-user@example.com']);
    }

    protected function tearDown(): void
    {
        File::delete($this->sourcePath);
        File::delete($this->targetPath);

        parent::tearDown();
    }

    public function test_it_replaces_application_tables_from_bundle_sqlite(): void
    {
        $counts = (new ApplicationBundleSqliteTableCopier)->copyFromSqliteFile($this->sourcePath);

        $this->assertSame(1, $counts['users'] ?? 0);
        $this->assertDatabaseHas('users', [
            'email' => 'mac-user@example.com',
            'name' => 'Mac User',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'cloud-user@example.com',
        ]);
    }
}
