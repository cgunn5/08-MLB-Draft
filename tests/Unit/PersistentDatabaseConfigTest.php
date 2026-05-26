<?php

namespace Tests\Unit;

use App\Support\PersistentDatabaseConfig;
use App\Support\PersistentStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PersistentDatabaseConfigTest extends TestCase
{
    private string $workDir;

    private string $persistent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = storage_path('app/testing-persistent-config-'.uniqid('', true));
        $this->persistent = $this->workDir.'/persistent/database.sqlite';
        File::ensureDirectoryExists(dirname($this->persistent));

        config([
            'persistence.database' => $this->persistent,
            'persistence.apply_in_tests' => true,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->workDir.'/legacy/database.sqlite',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    public function test_it_moves_sqlite_from_database_folder_to_persistent_storage(): void
    {
        $legacy = $this->workDir.'/legacy/database.sqlite';
        File::ensureDirectoryExists(dirname($legacy));
        File::put($legacy, 'legacy-db-bytes');

        PersistentDatabaseConfig::apply();

        $this->assertFileExists($this->persistent);
        $this->assertSame('legacy-db-bytes', file_get_contents($this->persistent));
        $this->assertSame($this->persistent, config('database.connections.sqlite.database'));
    }
}
