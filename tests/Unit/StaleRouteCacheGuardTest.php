<?php

namespace Tests\Unit;

use App\Support\StaleRouteCacheGuard;
use PHPUnit\Framework\TestCase;

class StaleRouteCacheGuardTest extends TestCase
{
    public function test_drops_route_cache_when_route_files_are_newer(): void
    {
        $base = sys_get_temp_dir().'/stale-route-cache-guard-'.uniqid('', true);
        mkdir($base.'/bootstrap/cache', 0777, true);
        mkdir($base.'/routes', 0777, true);

        $cacheFile = $base.'/bootstrap/cache/routes-v7.php';
        file_put_contents($cacheFile, '<?php return [];');
        touch($cacheFile, 1_700_000_000);

        $routeFile = $base.'/routes/web.php';
        file_put_contents($routeFile, '<?php');
        touch($routeFile, 1_800_000_000);

        StaleRouteCacheGuard::invalidateIfStale($base);

        $this->assertFileDoesNotExist($cacheFile);
    }

    public function test_keeps_route_cache_when_route_files_are_older(): void
    {
        $base = sys_get_temp_dir().'/stale-route-cache-guard-'.uniqid('', true);
        mkdir($base.'/bootstrap/cache', 0777, true);
        mkdir($base.'/routes', 0777, true);

        $cacheFile = $base.'/bootstrap/cache/routes-v7.php';
        file_put_contents($cacheFile, '<?php return [];');
        touch($cacheFile, 1_900_000_000);

        $routeFile = $base.'/routes/web.php';
        file_put_contents($routeFile, '<?php');
        touch($routeFile, 1_800_000_000);

        StaleRouteCacheGuard::invalidateIfStale($base);

        $this->assertFileExists($cacheFile);
    }
}
