<?php

namespace Tests\Unit;

use App\Support\SqliteDatabaseFileBackup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SqliteDatabaseFileBackupTest extends TestCase
{
    #[Test]
    public function it_copies_sqlite_file_and_prunes_old_backups(): void
    {
        $work = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mlb-draft-backup-test-'.uniqid('', true);
        mkdir($work, 0755, true);
        $src = $work.DIRECTORY_SEPARATOR.'source.sqlite';
        file_put_contents($src, 'not-a-real-sqlite-header-but-copyable');

        $backupDir = $work.DIRECTORY_SEPARATOR.'backups';
        $a = SqliteDatabaseFileBackup::copyTo($src, $backupDir, 'database');
        $b = SqliteDatabaseFileBackup::copyTo($src, $backupDir, 'database');

        $this->assertFileExists($a);
        $this->assertFileExists($b);
        $this->assertSame(file_get_contents($src), file_get_contents($a));

        $oldTs = time() - (86400 * 90);
        touch($a, $oldTs, $oldTs);
        touch($b, time(), time());
        clearstatcache(true, $a);
        clearstatcache(true, $b);

        $deleted = SqliteDatabaseFileBackup::pruneByAge($backupDir, 30);
        $this->assertSame(1, $deleted);
        $this->assertFileDoesNotExist($a);
        $this->assertFileExists($b);

        unlink($b);
        @unlink($src);
        @rmdir($backupDir);
        @rmdir($work);
    }

    #[Test]
    public function it_rejects_in_memory_sqlite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqliteDatabaseFileBackup::copyTo(':memory:', sys_get_temp_dir(), 'database');
    }
}
