<?php

namespace Tests\Unit;

use App\Support\SqliteDatabaseBootstrap;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SqliteDatabaseBootstrapTest extends TestCase
{
    private string $workDir;

    private ?string $legacyBackup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workDir = storage_path('app/testing-sqlite-bootstrap-'.uniqid('', true));
        File::ensureDirectoryExists($this->workDir);

        $legacy = SqliteDatabaseBootstrap::legacyPath();
        if (is_file($legacy)) {
            $this->legacyBackup = $this->workDir.'/legacy-backup.sqlite';
            copy($legacy, $this->legacyBackup);
        }
    }

    protected function tearDown(): void
    {
        $legacy = SqliteDatabaseBootstrap::legacyPath();
        if ($this->legacyBackup !== null) {
            copy($this->legacyBackup, $legacy);
        } elseif (is_file($legacy)) {
            File::delete($legacy);
        }

        File::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    #[Test]
    public function it_adopts_legacy_database_file_when_target_is_missing(): void
    {
        $target = $this->workDir.'/target.sqlite';
        File::put(SqliteDatabaseBootstrap::legacyPath(), 'legacy-db-contents');

        config(['database.connections.sqlite.database' => $target]);

        $this->assertTrue(SqliteDatabaseBootstrap::adoptExistingFileIfAvailable($target));
        $this->assertFileExists($target);
        $this->assertSame('legacy-db-contents', file_get_contents($target));
    }
}
