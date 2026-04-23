<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable([
    'user_id',
    'upload_kind',
    'name',
    'original_filename',
    'disk',
    'path',
    'career_pg_source_upload_id',
    'header_row',
    'row_count',
    'column_order',
    'heat_rules',
    'heat_column_stats',
    'hs_profile_feed_slots',
    'dataset_browse_settings',
])]
class DataSourceUpload extends Model
{
    public const UPLOAD_KIND_FILE = 'file';

    public const UPLOAD_KIND_CAREER_PG_MASTER = 'career_pg_master';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careerPgSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'career_pg_source_upload_id');
    }

    public function isCareerPgMaster(): bool
    {
        return $this->upload_kind === self::UPLOAD_KIND_CAREER_PG_MASTER;
    }

    /**
     * Normalized slot keys for APIs and the data-sources UI.
     *
     * @return list<string>
     */
    public static function normalizeHsProfileFeedSlotList(?array $slots): array
    {
        $list = [];
        if (is_array($slots)) {
            foreach ($slots as $s) {
                if (is_string($s) && $s !== '') {
                    $list[] = $s;
                }
            }
        }

        return array_values(array_unique($list));
    }

    /**
     * Slots to show for HS profile feed checkboxes. Perfect Game career dataset mirrors its source upload
     * (the DB column on career rows stays null).
     *
     * @param  Collection<int, self>|null  $siblingUploads  All uploads for this user (avoids N+1 on index).
     * @return list<string>
     */
    public function resolvedHsProfileFeedSlotsForUi(?Collection $siblingUploads = null): array
    {
        if ($this->isCareerPgMaster() && $this->career_pg_source_upload_id !== null) {
            $source = $siblingUploads !== null
                ? $siblingUploads->firstWhere('id', (int) $this->career_pg_source_upload_id)
                : $this->careerPgSource()->first();
            if ($source !== null) {
                return self::normalizeHsProfileFeedSlotList($source->hs_profile_feed_slots);
            }

            return [];
        }

        return self::normalizeHsProfileFeedSlotList($this->hs_profile_feed_slots);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'header_row' => 'array',
            'column_order' => 'array',
            'heat_rules' => 'array',
            'heat_column_stats' => 'array',
            'hs_profile_feed_slots' => 'array',
            'dataset_browse_settings' => 'array',
        ];
    }
}
