<?php

namespace App\Support;

/**
 * Login-page data gathered safely so /login never 500s from bootstrap checks.
 */
final class LoginPageContext
{
    /**
     * @return array{
     *     needsFirstRunSetup: bool,
     *     laravelCloudSqliteMisconfiguration: bool,
     *     recoverableBackup: ?array<string, mixed>,
     *     installationPreviouslyCompleted: bool,
     * }
     */
    public static function forView(): array
    {
        try {
            return [
                'needsFirstRunSetup' => ApplicationDatabaseBootstrap::needsFirstRunSetup(),
                'laravelCloudSqliteMisconfiguration' => ApplicationDatabaseBootstrap::laravelCloudSqliteMisconfiguration(),
                'recoverableBackup' => ApplicationDatabaseBootstrap::latestRecoverableBackupSummary(),
                'installationPreviouslyCompleted' => ApplicationInstallationMarker::exists(),
            ];
        } catch (\Throwable) {
            return [
                'needsFirstRunSetup' => HostedEnvironment::isLaravelCloud(),
                'laravelCloudSqliteMisconfiguration' => HostedEnvironment::laravelCloudSqliteMisconfiguration(),
                'recoverableBackup' => null,
                'installationPreviouslyCompleted' => false,
            ];
        }
    }
}
