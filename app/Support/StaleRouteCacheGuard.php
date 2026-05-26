<?php

namespace App\Support;

final class StaleRouteCacheGuard
{
    /**
     * Drop cached routes when route files changed after the cache was built.
     *
     * Prevents 500s after deploy when navigation calls route names added in a
     * newer release while bootstrap/cache/routes-*.php still reflects old code.
     */
    public static function invalidateIfStale(string $basePath): void
    {
        $cacheFiles = glob($basePath.'/bootstrap/cache/routes-*.php') ?: [];
        if ($cacheFiles === []) {
            return;
        }

        $routeFiles = glob($basePath.'/routes/*.php') ?: [];
        if ($routeFiles === []) {
            return;
        }

        $newestRouteMtime = max(array_map(static fn (string $path): int => (int) filemtime($path), $routeFiles));
        $oldestCacheMtime = min(array_map(static fn (string $path): int => (int) filemtime($path), $cacheFiles));

        if ($newestRouteMtime <= $oldestCacheMtime) {
            return;
        }

        foreach ($cacheFiles as $cacheFile) {
            @unlink($cacheFile);
        }
    }
}
