<?php

namespace App\Console\Commands;

use App\Models\DataSourceUpload;
use App\Models\User;
use App\Support\CareerPgMasterUploadService;
use App\Support\DataSourcePortalMisplacementFixer;
use Illuminate\Console\Command;

/**
 * Fixes uploads whose display name belongs in the other portal (e.g. NCAA pitch types saved under HS Data).
 */
class RemapDatasetPortalsByName extends Command
{
    protected $signature = 'app:remap-dataset-portals-by-name
                            {--user= : Only uploads for this user id}
                            {--dry-run : List changes without saving}';

    protected $description = 'Move mis-filed library datasets to the correct portal by display name (NCAA pitch types → NCAA Data; HS overall → HS Data) and remap profile-feed slots.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userFilter = $this->option('user');

        $userIds = [];
        if ($userFilter !== null && $userFilter !== '') {
            $uid = (int) $userFilter;
            if (User::query()->whereKey($uid)->doesntExist()) {
                $this->error("No user with id {$uid}.");

                return self::FAILURE;
            }
            $userIds[] = $uid;
        } else {
            $userIds = DataSourceUpload::query()
                ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
                ->distinct()
                ->pluck('user_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        $changed = 0;
        $touchedUserIds = [];
        foreach ($userIds as $userId) {
            $user = User::query()->find($userId);
            if ($user === null) {
                continue;
            }

            if ($dryRun) {
                $uploads = DataSourceUpload::query()
                    ->where('user_id', $userId)
                    ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
                    ->get();
                foreach ($uploads as $upload) {
                    $target = DataSourcePortalMisplacementFixer::inferCorrectPortalForUploadName($upload->name);
                    if ($target === null || $target === $upload->dataset_portal) {
                        continue;
                    }
                    if (DataSourcePortalMisplacementFixer::isCareerPgSourceUpload($userId, $upload->id)) {
                        $this->warn("Skipping upload #{$upload->id} ({$upload->name}): Perfect Game career source.");

                        continue;
                    }
                    $this->line("[dry-run] Upload #{$upload->id} «{$upload->name}»: {$upload->dataset_portal} → {$target}");
                    $changed++;
                }

                continue;
            }

            $n = DataSourcePortalMisplacementFixer::fixMisplacedFileUploadsForUser($user);
            if ($n > 0) {
                $changed += $n;
                $touchedUserIds[$userId] = true;
            }
        }

        if ($dryRun) {
            $this->info($changed === 0 ? 'No matching uploads to remap.' : "Dry run: {$changed} upload(s) would be updated. Run without --dry-run to apply.");
        } else {
            foreach (array_keys($touchedUserIds) as $uid) {
                $u = User::query()->find($uid);
                if ($u !== null) {
                    CareerPgMasterUploadService::syncForUser($u);
                }
            }
            $this->info($changed === 0 ? 'No matching uploads were updated.' : "Updated {$changed} upload(s) and re-synced Perfect Game career masters where applicable.");
        }

        return self::SUCCESS;
    }
}
