<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class CareerPgMasterUploadService
{
    public const CAREER_DISPLAY_NAME = 'HS Stats - Perfect Game Career';

    /** Display label for the editable yearly Perfect Game CSV in HS Data. */
    public const YEARLY_DISPLAY_NAME = 'Perfect Game - Yearly';

    /**
     * Ensures a derived career master row exists when the user has a Perfect Game
     * dataset assigned to the HS profile, and removes it when they do not.
     */
    public static function syncForUser(User $user): void
    {
        $source = self::resolveCanonicalPerformancePgFileUpload($user);

        $existing = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();

        if ($source === null) {
            $existing?->delete();

            return;
        }

        self::ensureYearlyPerfectGameTabLabel($source);

        $materialized = CareerPgStatsAggregator::fromSourceUpload($source);

        if ($existing === null) {
            DataSourceUpload::query()->create([
                'user_id' => $user->id,
                'dataset_portal' => DataSourceUpload::PORTAL_HS,
                'upload_kind' => DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER,
                'name' => self::CAREER_DISPLAY_NAME,
                'original_filename' => 'career-pg-master',
                'disk' => 'local',
                'path' => '',
                'career_pg_source_upload_id' => $source->id,
                'header_row' => $materialized['headers'],
                'row_count' => $materialized['row_count'],
                'hs_profile_feed_slots' => null,
            ]);

            return;
        }

        $existing->name = self::CAREER_DISPLAY_NAME;
        $existing->career_pg_source_upload_id = $source->id;
        $existing->header_row = $materialized['headers'];
        $existing->row_count = $materialized['row_count'];
        $existing->hs_profile_feed_slots = null;
        $existing->save();
    }

    /**
     * HS file upload that backs Perfect Game yearly stats and the derived career master.
     * Prefers the file linked from the career master; otherwise the HS file with {@see DataSourceUpload::$row_count}
     * among uploads with the Perfect Game performance slot (tie-break lower id).
     */
    public static function resolveCanonicalPerformancePgFileUpload(User $user): ?DataSourceUpload
    {
        $id = self::resolveCanonicalPerformancePgFileUploadId($user->id);
        if ($id === null) {
            return null;
        }

        return DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->whereKey($id)
            ->first();
    }

    public static function resolveCanonicalPerformancePgFileUploadId(int $userId): ?int
    {
        $career = DataSourceUpload::query()
            ->where('user_id', $userId)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();
        if ($career !== null && $career->career_pg_source_upload_id !== null) {
            $src = DataSourceUpload::query()
                ->where('user_id', $userId)
                ->whereKey($career->career_pg_source_upload_id)
                ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
                ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
                ->first();
            if ($src !== null) {
                $slots = $src->hs_profile_feed_slots;
                if (is_array($slots) && in_array('performance_pg', $slots, true)) {
                    return $src->id;
                }
            }
        }

        $bestId = null;
        $bestRows = -1;
        $uploads = DataSourceUpload::query()
            ->where('user_id', $userId)
            ->where('dataset_portal', DataSourceUpload::PORTAL_HS)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
            ->orderBy('id')
            ->get();
        foreach ($uploads as $u) {
            $slots = $u->hs_profile_feed_slots;
            if (! is_array($slots) || ! in_array('performance_pg', $slots, true)) {
                continue;
            }
            $rc = (int) $u->row_count;
            if ($rc > $bestRows || ($rc === $bestRows && ($bestId === null || $u->id < $bestId))) {
                $bestRows = $rc;
                $bestId = $u->id;
            }
        }

        return $bestId;
    }

    /**
     * @deprecated Use {@see resolveCanonicalPerformancePgFileUpload} (same behavior).
     */
    public static function resolvePgSourceUpload(User $user): ?DataSourceUpload
    {
        return self::resolveCanonicalPerformancePgFileUpload($user);
    }

    public static function resolveSourceForCareerMaster(DataSourceUpload $career): ?DataSourceUpload
    {
        if (! $career->isCareerPgMaster()) {
            return null;
        }
        $id = $career->career_pg_source_upload_id;
        if ($id === null) {
            return null;
        }

        $source = DataSourceUpload::query()->whereKey($id)->where('user_id', $career->user_id)->first();
        if ($source === null
            || $source->upload_kind !== DataSourceUpload::UPLOAD_KIND_FILE
            || $source->dataset_portal !== DataSourceUpload::PORTAL_HS) {
            return null;
        }
        if ($source->path === '' || ! DataSourceUploadStorage::exists($source->disk, $source->path)) {
            return null;
        }

        return $source;
    }

    /**
     * Sets the HS Data tab label to {@see YEARLY_DISPLAY_NAME} for generic / placeholder names only.
     */
    public static function ensureYearlyPerfectGameTabLabel(DataSourceUpload $source): void
    {
        if ($source->upload_kind !== DataSourceUpload::UPLOAD_KIND_FILE
            || $source->dataset_portal !== DataSourceUpload::PORTAL_HS) {
            return;
        }
        $slots = $source->hs_profile_feed_slots;
        if (! is_array($slots) || ! in_array('performance_pg', $slots, true)) {
            return;
        }
        if (trim($source->name) === self::YEARLY_DISPLAY_NAME) {
            return;
        }
        if (! self::shouldNormalizeYearlyPgDisplayName((string) $source->name)) {
            return;
        }
        $source->name = self::YEARLY_DISPLAY_NAME;
        $source->save();
    }

    private static function shouldNormalizeYearlyPgDisplayName(string $name): bool
    {
        $t = Str::lower(trim($name));
        if ($t === '') {
            return true;
        }
        if ($t === Str::lower(self::CAREER_DISPLAY_NAME)) {
            return true;
        }

        $generic = [
            'hs stats - perfect game',
            'hs stats - perfect game career',
            'pg',
            'pg stats',
            'perfect game',
            'perfect game career',
        ];

        return in_array($t, $generic, true);
    }
}
