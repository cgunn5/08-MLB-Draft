<?php

namespace App\Console\Commands;

use App\Models\DataSourceUpload;
use App\Models\User;
use App\Support\CareerPgMasterUploadService;
use App\Support\DataSourceUploadStorage;
use App\Support\HsPerfectGamePerformanceUploadDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RecoverHsPerfectGamePerformanceSlot extends Command
{
    protected $signature = 'app:recover-hs-perfect-game-performance-slot
                            {--user= : User id (default: first user)}
                            {--dry-run : Show actions without saving}
                            {--reassign : Strip performance_pg from all HS file uploads, then assign the largest PG-shaped CSV (fixes wrong source after recovery)}';

    protected $description = 'Assign hs_profile_feed_slots performance_pg to the HS CSV that looks like Perfect Game yearly stats, preferring the largest row count. Use --reassign to move the slot off a mistaken upload.';

    public function handle(): int
    {
        $userId = $this->option('user');
        if ($userId !== null && $userId !== '') {
            $user = User::query()->find((int) $userId);
            if ($user === null) {
                $this->error("No user with id {$userId}.");

                return self::FAILURE;
            }
        } else {
            $user = User::query()->orderBy('id')->first();
            if ($user === null) {
                $this->error('No users exist.');

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $reassign = (bool) $this->option('reassign');

        if ($reassign && $dryRun) {
            $this->line('[dry-run] Would strip performance_pg from every HS file upload, sync (remove career master), then assign performance_pg to the largest PG-shaped CSV by row_count.');
        } elseif ($reassign && ! $dryRun) {
            $this->stripPerformancePgFromHsFileUploads($user);
            CareerPgMasterUploadService::syncForUser($user);
            $this->info('Removed performance_pg from HS file uploads and cleared the derived career master (if any).');
        }

        $uploads = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
            ->orderBy('id')
            ->get();

        $existingPg = $uploads->first(static function (DataSourceUpload $u): bool {
            $slots = $u->hs_profile_feed_slots;

            return is_array($slots) && in_array('performance_pg', $slots, true);
        });

        $pgShaped = $this->collectPgShapedHsFileUploads($uploads);
        if ($pgShaped === []) {
            if ($dryRun) {
                $this->comment('No HS file uploads with PG-style headers (YEAR + PA/OPS/AVG/OBP/SLG + ISO or BB%+K%).');
            }

            return self::SUCCESS;
        }

        usort($pgShaped, static function (DataSourceUpload $a, DataSourceUpload $b): int {
            if ($a->row_count !== $b->row_count) {
                return $b->row_count <=> $a->row_count;
            }

            return $a->id <=> $b->id;
        });
        /** @var DataSourceUpload $best */
        $best = $pgShaped[0];

        if ($existingPg !== null && ! $reassign) {
            $this->info("Perfect Game performance is already assigned to upload #{$existingPg->id} ({$existingPg->name}, {$existingPg->row_count} rows).");
            if ($existingPg->id !== $best->id || $existingPg->row_count < $best->row_count) {
                $this->comment(
                    "Largest PG-shaped CSV is #{$best->id} ({$best->name}, {$best->row_count} rows). Run with --reassign to use that file for yearly + career Perfect Game."
                );
            }

            return self::SUCCESS;
        }

        $candidatesForAssign = array_values(array_filter(
            $pgShaped,
            static function (DataSourceUpload $u): bool {
                $slots = $u->hs_profile_feed_slots;

                return ! is_array($slots) || $slots === [];
            },
        ));

        if ($candidatesForAssign === []) {
            $this->warn('No HS file upload with empty profile slots is PG-shaped (other slots may still be set).');

            return self::SUCCESS;
        }

        usort($candidatesForAssign, static function (DataSourceUpload $a, DataSourceUpload $b): int {
            if ($a->row_count !== $b->row_count) {
                return $b->row_count <=> $a->row_count;
            }

            return $a->id <=> $b->id;
        });
        /** @var DataSourceUpload $chosen */
        $chosen = $candidatesForAssign[0];

        if ($dryRun) {
            $this->line("[dry-run] Would assign performance_pg to #{$chosen->id} — {$chosen->name} ({$chosen->row_count} rows).");

            return self::SUCCESS;
        }

        $chosen->hs_profile_feed_slots = ['performance_pg'];
        $chosen->save();
        $this->info("Assigned performance_pg to #{$chosen->id} — {$chosen->name} ({$chosen->row_count} rows).");

        CareerPgMasterUploadService::syncForUser($user);
        $this->info('Ran Perfect Game career master sync.');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, DataSourceUpload>  $uploads
     * @return list<DataSourceUpload>
     */
    private function collectPgShapedHsFileUploads(Collection $uploads): array
    {
        $out = [];
        foreach ($uploads as $upload) {
            if ($upload->path === '' || ! DataSourceUploadStorage::exists($upload->disk, $upload->path)) {
                continue;
            }
            $headers = $upload->header_row;
            if (! is_array($headers) || $headers === []) {
                continue;
            }
            if (! HsPerfectGamePerformanceUploadDetector::headerRowLooksLikePgMultiYearCircuit($headers)) {
                continue;
            }
            $out[] = $upload;
        }

        return $out;
    }

    private function stripPerformancePgFromHsFileUploads(User $user): void
    {
        $uploads = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
            ->get();

        foreach ($uploads as $u) {
            $slots = $u->hs_profile_feed_slots;
            if (! is_array($slots) || ! in_array('performance_pg', $slots, true)) {
                continue;
            }
            $next = array_values(array_diff($slots, ['performance_pg']));
            $u->hs_profile_feed_slots = $next === [] ? null : $next;
            $u->save();
        }
    }
}
