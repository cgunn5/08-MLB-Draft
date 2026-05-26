<?php

namespace App\Support;

final class StaleRouteCacheGuard
{
    /**
     * Route names that must exist whenever a route cache file is present.
     *
     * @var list<string>
     */
    private const REQUIRED_ROUTE_NAMES = [
        'dashboard',
        'board.index',
        'ncaa-data-sources.index',
    ];

    /**
     * Drop cached routes when route files changed after the cache was built, or when
     * the cache is missing route names required by the current release.
     */
    public static function invalidateIfStale(string $basePath): void
    {
        $cacheFiles = glob($basePath.'/bootstrap/cache/routes-*.php') ?: [];
        if ($cacheFiles === []) {
            return;
        }

        if (self::cacheMissingRequiredRouteNames($cacheFiles)) {
            self::deleteCacheFiles($cacheFiles);

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

        self::deleteCacheFiles($cacheFiles);
    }

    /**
     * @param  list<string>  $cacheFiles
     */
    private static function cacheMissingRequiredRouteNames(array $cacheFiles): bool
    {
        foreach ($cacheFiles as $cacheFile) {
            $contents = @file_get_contents($cacheFile);
            if ($contents === false) {
                continue;
            }

            foreach (self::REQUIRED_ROUTE_NAMES as $routeName) {
                if (! str_contains($contents, $routeName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $cacheFiles
     */
    private static function deleteCacheFiles(array $cacheFiles): void
    {
        foreach ($cacheFiles as $cacheFile) {
            @unlink($cacheFile);
        }
    }
}
