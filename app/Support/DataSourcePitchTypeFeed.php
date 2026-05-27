<?php

namespace App\Support;

use App\Models\DataSourceUpload;

final class DataSourcePitchTypeFeed
{
    public const FB = 'FB';

    public const BB = 'BB';

    public const OS = 'OS';

    /** Profile slot that pitch-type CSVs feed when split across uploads. */
    public const PROFILE_SLOT = 'adjustability_pitch';

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return [self::FB, self::BB, self::OS];
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $upper = strtoupper(trim($value));

        return in_array($upper, self::allowed(), true) ? $upper : null;
    }

    public static function fromUpload(DataSourceUpload $upload): ?string
    {
        return self::normalize($upload->pitch_type_feed);
    }
}
