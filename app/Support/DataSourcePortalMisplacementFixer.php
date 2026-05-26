<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Corrects file uploads whose display name clearly belongs in the other portal (e.g. "NCAA - Pitch Types"
 * stored under HS Data). Used by library index pages and the remap artisan command.
 */
final class DataSourcePortalMisplacementFixer
{
    /**
     * @return int Number of uploads that were (or would be, when $dryRun) updated.
     */
    public static function fixMisplacedFileUploadsForUser(User $user, bool $dryRun = false): int
    {
        $changed = 0;
        $uploads = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
            ->get();

        foreach ($uploads as $upload) {
            $target = self::inferCorrectPortalForUploadName($upload->name);
            if ($target === null || $target === $upload->dataset_portal) {
                continue;
            }

            if (self::isCareerPgSourceUpload($user->id, $upload->id)) {
                continue;
            }

            $hsSlots = DataSourceUpload::normalizeHsProfileFeedSlotList($upload->hs_profile_feed_slots);
            $ncaaSlots = DataSourceUpload::normalizeNcaaProfileFeedSlotList($upload->ncaa_profile_feed_slots);

            if ($target === DataSourceUpload::PORTAL_NCAA) {
                $nextNcaa = self::mapHsSlotsToNcaaSlots($hsSlots);
                $nextHs = [];
            } else {
                $nextHs = self::mapNcaaSlotsToHsSlots($ncaaSlots);
                $nextNcaa = [];
            }

            if (! $dryRun) {
                $upload->dataset_portal = $target;
                $upload->hs_profile_feed_slots = $nextHs === [] ? null : $nextHs;
                $upload->ncaa_profile_feed_slots = $nextNcaa === [] ? null : $nextNcaa;
                $upload->save();
            }
            $changed++;
        }

        return $changed;
    }

    public static function isCareerPgSourceUpload(int $userId, int $uploadId): bool
    {
        return DataSourceUpload::query()
            ->where('user_id', $userId)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->where('career_pg_source_upload_id', $uploadId)
            ->exists();
    }

    /**
     * @return DataSourceUpload::PORTAL_*|null
     */
    public static function inferCorrectPortalForUploadName(string $name): ?string
    {
        $n = self::normalizePortalTitle($name);

        if (self::nameLooksLikeNcaaPitchTypes($n)) {
            return DataSourceUpload::PORTAL_NCAA;
        }

        if (self::nameLooksLikeHsOverall($n)) {
            return DataSourceUpload::PORTAL_HS;
        }

        return null;
    }

    /**
     * Lowercase, unicode dashes to ASCII hyphen, collapse whitespace, strip stray punctuation noise.
     */
    public static function normalizePortalTitle(string $name): string
    {
        $t = Str::lower(trim($name));
        $t = (string) preg_replace('/[\x{2013}\x{2014}\x{2212}\x{FE58}\x{FE63}\x{FF0D}]/u', '-', $t);
        $t = (string) preg_replace('/\s+/u', ' ', $t);
        $t = trim((string) preg_replace('/[^a-z0-9\-\s]+/u', ' ', $t));
        $t = (string) preg_replace('/\s+/u', ' ', $t);

        return trim($t);
    }

    private static function nameLooksLikeNcaaPitchTypes(string $normalized): bool
    {
        if ($normalized === 'ncaa - pitch types' || $normalized === 'ncaa pitch types') {
            return true;
        }

        return (bool) preg_match('/\bncaa\b/', $normalized)
            && (bool) preg_match('/\bpitch\b/', $normalized)
            && (bool) preg_match('/\btypes?\b/', $normalized);
    }

    private static function nameLooksLikeHsOverall(string $normalized): bool
    {
        if (str_contains($normalized, 'perfect game')) {
            return false;
        }
        if ((bool) preg_match('/\bncaa\b/', $normalized)) {
            return false;
        }

        if ($normalized === 'hs - overall' || $normalized === 'hs overall') {
            return true;
        }

        return (bool) preg_match('/\bhs\b/', $normalized)
            && (bool) preg_match('/\boverall\b/', $normalized);
    }

    /**
     * @param  list<string>  $hsSlots
     * @return list<string>
     */
    public static function mapHsSlotsToNcaaSlots(array $hsSlots): array
    {
        $allowed = array_flip(NcaaRangerTraitsSheetLayout::ncaaProfileSlotKeys());
        $map = [
            'performance_overall' => 'performance_ncaa',
            'performance_tusa' => null,
            'performance_pg' => null,
            'approach_overall' => 'approach_ncaa',
            'impact_overall' => 'engine_overall',
            'adjustability_pitch' => 'adjustability_pitch',
            'adjustability_lr' => 'platoon_ncaa',
        ];
        $out = [];
        foreach ($hsSlots as $s) {
            $to = $map[$s] ?? null;
            if ($to !== null && isset($allowed[$to])) {
                $out[] = $to;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<string>  $ncaaSlots
     * @return list<string>
     */
    public static function mapNcaaSlotsToHsSlots(array $ncaaSlots): array
    {
        $allowed = array_flip(HsRangerTraitsSheetLayout::hsProfileSlotKeys());
        $map = [
            'performance_ncaa' => 'performance_overall',
            'performance_summer' => null,
            'approach_ncaa' => 'approach_overall',
            'approach_summer' => null,
            'adjustability_overall' => null,
            'adjustability_pitch' => 'adjustability_pitch',
            'engine_overall' => 'impact_overall',
            'platoon_ncaa' => 'adjustability_lr',
        ];
        $out = [];
        foreach ($ncaaSlots as $s) {
            $to = $map[$s] ?? null;
            if ($to !== null && isset($allowed[$to])) {
                $out[] = $to;
            }
        }

        return array_values(array_unique($out));
    }
}
