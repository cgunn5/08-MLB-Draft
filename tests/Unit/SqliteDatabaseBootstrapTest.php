<?php

namespace Tests\Unit;

use App\Support\SqliteDatabaseBootstrap;
use PHPUnit\Framework\TestCase;

class SqliteDatabaseBootstrapTest extends TestCase
{
    public function test_ensure_file_exists_creates_missing_database_file(): void
    {
        $base = sys_get_temp_dir().'/sqlite-bootstrap-'.uniqid('', true);
        mkdir($base, 0777, true);
        $path = $base.'/database/database.sqlite';

        $this->assertTrue(SqliteDatabaseBootstrap::ensureFileExists($path));
        $this->assertFileExists($path);
        $this->assertFalse(SqliteDatabaseBootstrap::ensureFileExists($path));
    }
}
