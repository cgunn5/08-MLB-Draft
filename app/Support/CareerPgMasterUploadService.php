<?php

namespace App\Support;

use App\Models\DataSourceUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class CareerPgMasterUploadService
{
    public const CAREER_DISPLAY_NAME = 'HS Stats - Perfect Game Career';

    /**
     * Ensures a derived career master row exists when the user has a Perfect Game
     * dataset assigned to the HS profile, and removes it when they do not.
     */
    public static function syncForUser(User $user): void
    {
        $source = self::resolvePgSourceUpload($user);

        $existing = DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_CAREER_PG_MASTER)
            ->first();

        if ($source === null) {
            $existing?->delete();

            return;
        }

        $materialized = CareerPgStatsAggregator::fromSourceUpload($source);

        if ($existing === null) {
            DataSourceUpload::query()->create([
                'user_id' => $user->id,
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

    public static function resolvePgSourceUpload(User $user): ?DataSourceUpload
    {
        return DataSourceUpload::query()
            ->where('user_id', $user->id)
            ->where('upload_kind', DataSourceUpload::UPLOAD_KIND_FILE)
            ->orderBy('id')
            ->get()
            ->first(static function (DataSourceUpload $upload): bool {
                $slots = $upload->hs_profile_feed_slots;

                return is_array($slots) && in_array('performance_pg', $slots, true);
            });
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
        if ($source === null || $source->upload_kind !== DataSourceUpload::UPLOAD_KIND_FILE) {
            return null;
        }
        if ($source->path === '' || ! is_file(Storage::disk($source->disk)->path($source->path))) {
            return null;
        }

        return $source;
    }
}
